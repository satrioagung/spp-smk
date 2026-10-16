<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModuleTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function seedModule(): void
    {
        $this->seed([RoleSeeder::class, InstitutionSeeder::class, UserSeeder::class]);
    }

    public function test_super_admin_can_view_institutions(): void
    {
        $this->seedModule();
        $user = User::where('email', 'superadmin@sip-spp.id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('institutions.index'));

        $response->assertOk()->assertSee('SMK Satria Agung')->assertSee('SMP Harapan Bangsa');
    }

    public function test_non_super_admin_cannot_manage_institutions(): void
    {
        $this->seedModule();
        $user = User::where('email', 'admin@smksatria.id')->firstOrFail();

        $this->actingAs($user)->get(route('institutions.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_upload_and_toggle_institution(): void
    {
        Storage::fake('public');
        $this->seedModule();
        $user = User::where('email', 'superadmin@sip-spp.id')->firstOrFail();
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($user)->post(route('institutions.store'), [
            'nama' => 'SMK Bintang Sembilan',
            'jenjang' => 'SMK',
            'prefix_nomor_struk' => 'B9',
            'warna_tema' => '#123456',
            'logo' => $logo,
            'akun_nama' => 'Admin Bintang Sembilan',
            'akun_email' => 'admin@bintangsembilan.id',
            'akun_password' => 'password-bintang',
            'akun_password_confirmation' => 'password-bintang',
        ]);

        $response->assertRedirect(route('institutions.index'));
        $institution = Institution::where('nama', 'SMK Bintang Sembilan')->firstOrFail();
        Storage::disk('public')->assertExists($institution->logo);
        $this->assertDatabaseHas('users', ['institution_id' => $institution->id, 'email' => 'admin@bintangsembilan.id']);
        $this->assertTrue(User::where('email', 'admin@bintangsembilan.id')->firstOrFail()->hasRole('admin_sekolah'));

        $this->actingAs($user)->post(route('institutions.toggle', $institution))->assertRedirect();
        $this->assertDatabaseHas('institutions', ['id' => $institution->id, 'aktif' => false]);
    }

    public function test_super_admin_can_switch_active_institution(): void
    {
        $this->seedModule();
        $user = User::where('email', 'superadmin@sip-spp.id')->firstOrFail();
        $institution = Institution::where('nama', 'SMP Harapan Bangsa')->firstOrFail();

        $this->actingAs($user)->post(route('switch.tenant', $institution))->assertRedirect(route('dashboard'));

        $this->assertEquals($institution->id, session('active_institution_id'));
    }

    public function test_super_admin_can_edit_institution_account(): void
    {
        $this->seedModule();
        $user = User::where('email', 'superadmin@sip-spp.id')->firstOrFail();
        $institution = Institution::where('nama', 'SMK Satria Agung')->firstOrFail();

        $this->actingAs($user)->put(route('institutions.update', $institution), [
            'nama' => $institution->nama,
            'jenjang' => $institution->jenjang,
            'prefix_nomor_struk' => $institution->prefix_nomor_struk,
            'akun_nama' => 'Admin Satria Baru',
            'akun_email' => 'admin.satria.baru@example.com',
            'akun_password' => 'password-baru',
            'akun_password_confirmation' => 'password-baru',
        ])->assertRedirect(route('institutions.index'));

        $account = User::where('email', 'admin.satria.baru@example.com')->firstOrFail();
        $this->assertSame('Admin Satria Baru', $account->name);
        $this->assertTrue($account->hasRole('admin_sekolah'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password-baru', $account->password));
    }

    public function test_super_admin_can_edit_own_profile_and_password(): void
    {
        $this->seedModule();
        $user = User::where('email', 'superadmin@sip-spp.id')->firstOrFail();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Super Admin Utama',
            'email' => 'superadmin.utama@sip-spp.id',
        ])->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Super Admin Utama', 'email' => 'superadmin.utama@sip-spp.id']);

        $this->actingAs($user->fresh())->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'password-super-baru',
            'password_confirmation' => 'password-super-baru',
        ])->assertRedirect();
    }
}
