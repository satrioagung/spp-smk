<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\SchoolYear;
use Illuminate\Database\Seeder;

class SchoolYearSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Institution::query()->get() as $institution) {
            SchoolYear::where('institution_id', $institution->id)->update(['aktif' => false]);

            SchoolYear::updateOrCreate(
                ['institution_id' => $institution->id, 'nama' => '2024/2025'],
                ['tanggal_mulai' => '2024-07-01', 'tanggal_selesai' => '2025-06-30', 'aktif' => true],
            );
            SchoolYear::updateOrCreate(
                ['institution_id' => $institution->id, 'nama' => '2025/2026'],
                ['tanggal_mulai' => '2025-07-01', 'tanggal_selesai' => '2026-06-30', 'aktif' => false],
            );
        }
    }
}
