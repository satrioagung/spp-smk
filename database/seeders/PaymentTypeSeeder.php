<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\PaymentType;
use App\Models\SchoolYear;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::where('nama', 'SMK Satria Agung')->first();
        $schoolYear = $institution?->schoolYears()->where('nama', '2024/2025')->first();
        if (! $institution || ! $schoolYear) {
            return;
        }

        foreach ([
            ['nama' => 'SPP Bulanan', 'kode' => 'SPP', 'nominal_default' => 200000, 'tipe' => 'bulanan', 'bisa_cicil' => false, 'wajib' => true],
            ['nama' => 'Ujian Semester', 'kode' => 'UAS', 'nominal_default' => 150000, 'tipe' => 'tahunan', 'bisa_cicil' => true, 'wajib' => true],
            ['nama' => 'Seragam', 'kode' => 'SRG', 'nominal_default' => 350000, 'tipe' => 'sekali', 'bisa_cicil' => true, 'wajib' => true],
            ['nama' => 'Dana Kegiatan', 'kode' => 'DKS', 'nominal_default' => 50000, 'tipe' => 'bebas', 'bisa_cicil' => false, 'wajib' => false],
        ] as $data) {
            PaymentType::updateOrCreate(['institution_id' => $institution->id, 'school_year_id' => $schoolYear->id, 'kode' => $data['kode']], $data + ['aktif' => true]);
        }
    }
}
