<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $smk = Institution::where('nama', 'SMK Satria Agung')->firstOrFail();

        $accounts = [
            ['name' => 'Super Admin', 'email' => 'superadmin@sip-spp.id', 'role' => 'super_admin', 'institution_id' => null],
            ['name' => 'Admin SMK', 'email' => 'admin@smksatria.id', 'role' => 'admin_sekolah', 'institution_id' => $smk->id],
            ['name' => 'Bendahara SMK', 'email' => 'bendahara@smksatria.id', 'role' => 'bendahara', 'institution_id' => $smk->id],
            ['name' => 'Siswa Demo', 'email' => 'siswa@smksatria.id', 'role' => 'siswa', 'institution_id' => $smk->id],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(['email' => $account['email']], [
                'name' => $account['name'], 'password' => 'password', 'institution_id' => $account['institution_id'], 'aktif' => true,
            ]);
            $user->syncRoles([$account['role']]);
        }
    }
}
