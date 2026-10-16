<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::where('nama', 'SMK Satria Agung')->first();
        $schoolYear = $institution?->schoolYears()->where('nama', '2024/2025')->first();

        if (! $institution || ! $schoolYear) {
            return;
        }

        foreach (['X TKJ 1', 'X TKJ 2', 'X AKUN 1', 'XI TKJ 1', 'XI AKUN 1', 'XII TKJ 1', 'XII AKUN 1'] as $name) {
            SchoolClass::updateOrCreate(
                ['institution_id' => $institution->id, 'school_year_id' => $schoolYear->id, 'nama_kelas' => $name],
                ['tingkat' => str($name)->before(' ')->toString(), 'aktif' => true],
            );
        }
    }
}
