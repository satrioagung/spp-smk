<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private int $number = 0;

    public function __construct(private readonly int $institutionId, private readonly array $filters = [])
    {
    }

    public function query(): Builder
    {
        return Student::with(['schoolClass', 'schoolYear'])
            ->where('institution_id', $this->institutionId)
            ->when($this->filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($subQuery) => $subQuery
                ->whereRaw('LOWER(nama_lengkap) LIKE ?', ['%'.strtolower((string) $search).'%'])
                ->orWhereRaw('LOWER(nis) LIKE ?', ['%'.strtolower((string) $search).'%'])))
            ->when($this->filters['class_id'] ?? null, fn ($query, $classId) => $query->where('class_id', $classId))
            ->when($this->filters['school_year_id'] ?? null, fn ($query, $yearId) => $query->where('school_year_id', $yearId))
            ->when(($this->filters['status'] ?? null) === 'active', fn ($query) => $query->where('aktif', true))
            ->when(($this->filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('aktif', false))
            ->orderBy('nama_lengkap');
    }

    public function headings(): array
    {
        return ['No', 'NIS', 'NISN', 'Nama', 'Jenis Kelamin', 'Kelas', 'No HP Ortu', 'Status'];
    }

    public function map($student): array
    {
        return [
            ++$this->number,
            $student->nis,
            $student->nisn ?: '-',
            $student->nama_lengkap,
            $student->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
            $student->schoolClass?->nama_kelas ?: '-',
            $student->no_hp_ortu ?: '-',
            $student->aktif ? 'Aktif' : 'Nonaktif',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
