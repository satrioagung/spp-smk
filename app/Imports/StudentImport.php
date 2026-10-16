<?php

namespace App\Imports;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Throwable;

class StudentImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private int $institutionId;
    private int $schoolYearId;
    private int $added = 0;
    private int $updated = 0;
    private int $failedRows = 0;

    public function __construct(int $institutionId, int $schoolYearId)
    {
        $this->institutionId = $institutionId;
        $this->schoolYearId = $schoolYearId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $nis = trim((string) ($row['nis'] ?? ''));
            $className = trim((string) ($row['kelas'] ?? ''));
            $schoolClass = SchoolClass::where('institution_id', $this->institutionId)
                ->where('school_year_id', $this->schoolYearId)
                ->where('nama_kelas', $className)
                ->first();

            if (! $schoolClass || $nis === '') {
                $this->failedRows++;
                continue;
            }

            $student = Student::withTrashed()->where('institution_id', $this->institutionId)->where('nis', $nis)->first();
            $data = [
                'institution_id' => $this->institutionId,
                'school_year_id' => $this->schoolYearId,
                'class_id' => $schoolClass->id,
                'nis' => $nis,
                'nisn' => filled($row['nisn'] ?? null) ? trim((string) $row['nisn']) : null,
                'nama_lengkap' => trim((string) ($row['nama_lengkap'] ?? '')),
                'jenis_kelamin' => strtoupper(trim((string) ($row['jenis_kelamin'] ?? ''))),
                'no_hp_ortu' => filled($row['no_hp_ortu'] ?? null) ? trim((string) $row['no_hp_ortu']) : null,
                'aktif' => $this->toBoolean($row['aktif'] ?? true),
            ];

            if ($student) {
                if ($student->trashed()) {
                    $student->restore();
                }
                $student->update($data);
                $this->updated++;
            } else {
                Student::create($data);
                $this->added++;
            }
        }
    }

    public function rules(): array
    {
        return [
            '*.nis' => ['required', 'max:10'],
            '*.nama_lengkap' => ['required', 'max:100'],
            '*.jenis_kelamin' => ['required', 'in:L,P'],
            '*.kelas' => [
                'required',
                Rule::exists('classes', 'nama_kelas')->where(fn ($query) => $query
                    ->where('institution_id', $this->institutionId)
                    ->where('school_year_id', $this->schoolYearId)),
            ],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            '*.nis.required' => 'NIS wajib diisi.',
            '*.nis.max' => 'NIS maksimal 10 karakter.',
            '*.nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            '*.jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
            '*.kelas.exists' => 'Kelas tidak ditemukan pada tahun ajaran aktif.',
        ];
    }

    public function onError(Throwable $e): void
    {
        $this->failedRows++;
    }

    public function added(): int
    {
        return $this->added;
    }

    public function updated(): int
    {
        return $this->updated;
    }

    public function failed(): int
    {
        return $this->failedRows + count($this->failures()) + count($this->errors());
    }

    private function toBoolean(mixed $value): bool
    {
        return ! in_array(strtolower(trim((string) $value)), ['0', 'false', 'tidak', 'nonaktif'], true);
    }
}
