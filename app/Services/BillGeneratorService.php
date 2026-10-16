<?php

namespace App\Services;

use App\Enums\PaymentTypeKind;
use App\Models\Bill;
use App\Models\PaymentType;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class BillGeneratorService
{
    public function generateMassal(array $params): array
    {
        $paymentType = $this->paymentType($params);
        return $this->generateForStudents($this->studentsForTarget($params, $paymentType), $paymentType, $params);
    }

    public function generatePerKelas(int $classId, array $params): array
    {
        $params['target'] = 'class';
        $params['class_id'] = $classId;
        return $this->generateMassal($params);
    }

    public function generatePerSiswa(int $studentId, array $params): array
    {
        $params['target'] = 'student';
        $params['student_id'] = $studentId;
        return $this->generateMassal($params);
    }

    public function preview(array $params): array
    {
        $paymentType = $this->paymentType($params);
        $students = $this->studentsForTarget($params, $paymentType);
        $months = $this->months($paymentType, $params);
        $rows = [];
        $willCreate = 0;
        $skipped = 0;

        foreach ($students as $student) {
            foreach ($months as $month) {
                $exists = $this->billExists($student, $paymentType, $month, (int) $params['tahun']);
                $exists ? $skipped++ : $willCreate++;
                $rows[] = ['student' => $student, 'month' => $month, 'year' => (int) $params['tahun'], 'nominal' => (float) $params['nominal'], 'exists' => $exists];
            }
        }

        return ['rows' => $rows, 'willCreate' => $willCreate, 'skipped' => $skipped, 'studentsCount' => $students->count()];
    }

    private function generateForStudents(Collection $students, PaymentType $paymentType, array $params): array
    {
        $result = ['berhasil' => 0, 'dilewati' => 0, 'gagal' => 0];
        $months = $this->months($paymentType, $params);

        foreach ($students as $student) {
            foreach ($months as $month) {
                if ($this->billExists($student, $paymentType, $month, (int) $params['tahun'])) {
                    $result['dilewati']++;
                    continue;
                }

                try {
                    if ($this->createBill($student, $paymentType, array_merge($params, ['bulan' => $month]))) {
                        $result['berhasil']++;
                    } else {
                        $result['dilewati']++;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $result['gagal']++;
                }
            }
        }

        return $result;
    }

    private function createBill(Student $student, PaymentType $paymentType, array $params): ?Bill
    {
        if ($this->billExists($student, $paymentType, $params['bulan'] ?? null, (int) $params['tahun'])) {
            return null;
        }

        return DB::transaction(function () use ($student, $paymentType, $params): Bill {
            $bill = Bill::create([
                'institution_id' => $paymentType->institution_id,
                'school_year_id' => $paymentType->school_year_id,
                'student_id' => $student->id,
                'payment_type_id' => $paymentType->id,
                'bulan' => $params['bulan'] ?? null,
                'tahun' => (int) $params['tahun'],
                'nominal' => $params['nominal'] ?? $paymentType->nominal_default,
                'nominal_terbayar' => 0,
                'status' => 'belum_bayar',
                'jatuh_tempo' => $params['jatuh_tempo'] ?? null,
                'keterangan' => $params['keterangan'] ?? null,
            ]);
            return $bill->updateStatus()->save() ? $bill : $bill;
        });
    }

    private function paymentType(array $params): PaymentType
    {
        return PaymentType::where('institution_id', (int) $params['institution_id'])->where('aktif', true)->findOrFail((int) $params['payment_type_id']);
    }

    private function studentsForTarget(array $params, PaymentType $paymentType): Collection
    {
        $query = Student::with('schoolClass')->where('institution_id', $paymentType->institution_id)->where('school_year_id', $paymentType->school_year_id)->where('aktif', true);

        return match ($params['target'] ?? 'all') {
            'class' => $query->where('class_id', (int) $params['class_id'])->orderBy('nama_lengkap')->get(),
            'student' => $query->whereKey((int) $params['student_id'])->orderBy('nama_lengkap')->get(),
            default => $query->orderBy('nama_lengkap')->get(),
        };
    }

    private function months(PaymentType $paymentType, array $params): array
    {
        if ($paymentType->tipe !== PaymentTypeKind::BULANAN) {
            return [null];
        }

        return collect($params['bulan'] ?? [])->map(fn ($month) => (int) $month)->unique()->sort()->values()->all();
    }

    private function billExists(Student $student, PaymentType $paymentType, ?int $month, int $year): bool
    {
        return Bill::withTrashed()->where('institution_id', $paymentType->institution_id)->where('student_id', $student->id)->where('payment_type_id', $paymentType->id)->where('tahun', $year)->when($month === null, fn ($query) => $query->whereNull('bulan'), fn ($query) => $query->where('bulan', $month))->exists();
    }
}
