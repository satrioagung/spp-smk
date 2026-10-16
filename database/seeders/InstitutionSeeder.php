<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        Institution::updateOrCreate(['nama' => 'SMK Satria Agung'], [
            'jenjang' => 'SMK', 'alamat' => 'Jl. Pendidikan No. 1', 'warna_tema' => '#1d4ed8', 'prefix_nomor_struk' => 'SMK', 'aktif' => true,
        ]);
        Institution::updateOrCreate(['nama' => 'SMP Harapan Bangsa'], [
            'jenjang' => 'SMP', 'alamat' => 'Jl. Harapan No. 9', 'warna_tema' => '#047857', 'prefix_nomor_struk' => 'SMP', 'aktif' => true,
        ]);
    }
}
