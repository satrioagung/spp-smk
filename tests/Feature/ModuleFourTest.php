<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleFourTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_payment_type_and_generate_monthly_bills(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@smksatria.id')->firstOrFail();
        $year = SchoolYear::where('institution_id', $user->institution_id)->where('aktif', true)->firstOrFail();
        $class = SchoolClass::where('institution_id', $user->institution_id)->where('school_year_id', $year->id)->firstOrFail();
        Student::create(['institution_id' => $user->institution_id, 'school_year_id' => $year->id, 'class_id' => $class->id, 'nis' => 'M4001', 'nama_lengkap' => 'Siswa Modul Empat', 'jenis_kelamin' => 'L', 'aktif' => true]);

        $this->actingAs($user)->post(route('payment-types.store'), ['school_year_id' => $year->id, 'nama' => 'SPP Bulanan', 'kode' => 'SPP', 'nominal_default' => 200000, 'tipe' => 'bulanan', 'wajib' => 1, 'aktif' => 1])->assertRedirect();
        $paymentType = $user->institution->paymentTypes()->where('kode', 'SPP')->firstOrFail();

        $this->actingAs($user)->post(route('bills.preview'), ['payment_type_id' => $paymentType->id, 'target' => 'all', 'bulan' => [1], 'tahun' => 2025, 'nominal' => 200000])->assertOk()->assertSee('Siswa Modul Empat')->assertSee('Baru');
        $this->actingAs($user)->post(route('bills.generate'), ['payment_type_id' => $paymentType->id, 'target' => 'all', 'bulan' => [1], 'tahun' => 2025, 'nominal' => 200000, 'confirm' => 1])->assertRedirect(route('bills.index'))->assertSessionHas('success', 'Berhasil membuat 1 tagihan, 0 tagihan dilewati (sudah ada).');

        $this->assertDatabaseHas('bills', ['student_id' => Student::where('nis', 'M4001')->value('id'), 'payment_type_id' => $paymentType->id, 'bulan' => 1, 'tahun' => 2025, 'status' => 'belum_bayar']);
        $this->actingAs($user)->post(route('bills.generate'), ['payment_type_id' => $paymentType->id, 'target' => 'all', 'bulan' => [1], 'tahun' => 2025, 'nominal' => 200000, 'confirm' => 1])->assertSessionHas('success', 'Berhasil membuat 0 tagihan, 1 tagihan dilewati (sudah ada).');
    }

    public function test_bill_filters_and_deletion_rules_are_scoped(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@smksatria.id')->firstOrFail();
        $year = SchoolYear::where('institution_id', $user->institution_id)->where('aktif', true)->firstOrFail();
        $class = SchoolClass::where('institution_id', $user->institution_id)->where('school_year_id', $year->id)->firstOrFail();
        $student = Student::create(['institution_id' => $user->institution_id, 'school_year_id' => $year->id, 'class_id' => $class->id, 'nis' => 'M4002', 'nama_lengkap' => 'Siswa Cari Tagihan', 'jenis_kelamin' => 'P', 'aktif' => true]);
        $paymentType = $user->institution->paymentTypes()->create(['school_year_id' => $year->id, 'nama' => 'Ujian Modul', 'kode' => 'UAS4', 'nominal_default' => 150000, 'tipe' => 'tahunan', 'wajib' => true, 'aktif' => true]);
        $bill = Bill::create(['institution_id' => $user->institution_id, 'school_year_id' => $year->id, 'student_id' => $student->id, 'payment_type_id' => $paymentType->id, 'tahun' => 2025, 'nominal' => 150000, 'nominal_terbayar' => 0, 'status' => 'belum_bayar']);

        $this->actingAs($user)->get(route('bills.index', ['search' => 'cari tagihan']))->assertOk()->assertSee('Siswa Cari Tagihan')->assertSee('Belum Bayar');
        $this->actingAs($user)->delete(route('payment-types.destroy', $paymentType))->assertSessionHasErrors('payment_type');
        $this->actingAs($user)->delete(route('bills.destroy', $bill))->assertRedirect(route('bills.index'));
        $this->assertSoftDeleted('bills', ['id' => $bill->id]);
    }
}
