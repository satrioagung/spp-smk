<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleFiveTest extends TestCase
{
    use RefreshDatabase;

    private function paymentFixture(): array
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@smksatria.id')->firstOrFail();
        $year = SchoolYear::where('institution_id', $user->institution_id)->where('aktif', true)->firstOrFail();
        $class = SchoolClass::where('institution_id', $user->institution_id)->where('school_year_id', $year->id)->firstOrFail();
        $student = Student::create(['institution_id' => $user->institution_id, 'school_year_id' => $year->id, 'class_id' => $class->id, 'nis' => 'M5001', 'nama_lengkap' => 'Siswa Pembayaran', 'jenis_kelamin' => 'L', 'aktif' => true]);
        $type = PaymentType::create(['institution_id' => $user->institution_id, 'school_year_id' => $year->id, 'nama' => 'SPP Modul Lima', 'kode' => 'M5', 'nominal_default' => 200000, 'tipe' => 'bulanan', 'aktif' => true]);
        $bill = Bill::create(['institution_id' => $user->institution_id, 'school_year_id' => $year->id, 'student_id' => $student->id, 'payment_type_id' => $type->id, 'bulan' => 1, 'tahun' => 2025, 'nominal' => 200000, 'nominal_terbayar' => 0, 'status' => 'belum_bayar']);
        return compact('user', 'bill');
    }

    public function test_payment_updates_bill_status_and_supports_installments(): void
    {
        ['user' => $user, 'bill' => $bill] = $this->paymentFixture();
        $this->actingAs($user)->post(route('payments.store'), ['bill_id' => $bill->id, 'tanggal_bayar' => '2025-01-10', 'metode_bayar' => 'tunai', 'nominal_bayar' => 75000])->assertRedirect();
        $bill->refresh();
        $this->assertSame('sebagian', $bill->status);
        $this->assertSame(75000.0, (float) $bill->nominal_terbayar);

        $this->actingAs($user)->post(route('payments.store'), ['bill_id' => $bill->id, 'tanggal_bayar' => '2025-01-20', 'metode_bayar' => 'qris', 'nominal_bayar' => 125000])->assertRedirect();
        $bill->refresh();
        $this->assertSame('lunas', $bill->status);
        $this->assertSame(200000.0, (float) $bill->nominal_terbayar);
        $this->assertSame(2, Payment::count());
    }

    public function test_payment_cannot_exceed_bill_and_delete_rolls_status_back(): void
    {
        ['user' => $user, 'bill' => $bill] = $this->paymentFixture();
        $this->actingAs($user)->from(route('payments.create', ['bill_id' => $bill->id]))->post(route('payments.store'), ['bill_id' => $bill->id, 'tanggal_bayar' => '2025-01-10', 'metode_bayar' => 'transfer', 'nominal_bayar' => 200001])->assertSessionHasErrors('nominal_bayar');
        $this->actingAs($user)->post(route('payments.store'), ['bill_id' => $bill->id, 'tanggal_bayar' => '2025-01-10', 'metode_bayar' => 'transfer', 'nominal_bayar' => 200000]);
        $payment = Payment::firstOrFail();
        $this->assertSame('lunas', $bill->refresh()->status);

        $this->actingAs($user)->delete(route('payments.destroy', $payment))->assertRedirect(route('payments.index'));
        $this->assertSame('belum_bayar', $bill->refresh()->status);
        $this->assertSame(0.0, (float) $bill->nominal_terbayar);
    }

    public function test_payment_detail_history_and_receipt_pdf_are_available(): void
    {
        ['user' => $user, 'bill' => $bill] = $this->paymentFixture();
        $this->actingAs($user)->post(route('payments.store'), ['bill_id' => $bill->id, 'tanggal_bayar' => '2025-01-10', 'metode_bayar' => 'tunai', 'nominal_bayar' => 200000]);
        $payment = Payment::firstOrFail();
        $this->actingAs($user)->get(route('payments.index', ['tanggal_mulai' => '2025-01-01', 'tanggal_selesai' => '2025-01-31']))->assertOk()->assertSee($payment->nomor_transaksi)->assertSee('Siswa Pembayaran');
        $this->actingAs($user)->get(route('payments.show', $payment))->assertOk()->assertSee('Cetak Struk');
        $response = $this->actingAs($user)->get(route('payments.struk', $payment));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_one_payment_can_cover_multiple_months_and_payment_types(): void
    {
        ['user' => $user, 'bill' => $january] = $this->paymentFixture();
        $student = $january->student;
        $type = $january->paymentType;

        $february = Bill::create([
            'institution_id' => $january->institution_id,
            'school_year_id' => $january->school_year_id,
            'student_id' => $student->id,
            'payment_type_id' => $type->id,
            'bulan' => 2,
            'tahun' => 2025,
            'nominal' => 200000,
            'status' => 'belum_bayar',
        ]);
        $march = Bill::create([
            'institution_id' => $january->institution_id,
            'school_year_id' => $january->school_year_id,
            'student_id' => $student->id,
            'payment_type_id' => $type->id,
            'bulan' => 3,
            'tahun' => 2025,
            'nominal' => 200000,
            'status' => 'belum_bayar',
        ]);
        $examType = PaymentType::create([
            'institution_id' => $january->institution_id,
            'school_year_id' => $january->school_year_id,
            'nama' => 'UAS Modul Lima',
            'kode' => 'UAS5',
            'nominal_default' => 100000,
            'tipe' => 'sekali',
            'aktif' => true,
        ]);
        $exam = Bill::create([
            'institution_id' => $january->institution_id,
            'school_year_id' => $january->school_year_id,
            'student_id' => $student->id,
            'payment_type_id' => $examType->id,
            'tahun' => 2025,
            'nominal' => 100000,
            'status' => 'belum_bayar',
        ]);

        $ids = [$january->id, $february->id, $march->id, $exam->id];
        $this->actingAs($user)->get(route('payments.create', ['bill_ids' => $ids]))
            ->assertOk()
            ->assertSee('UAS Modul Lima')
            ->assertSee('Februari');

        $this->actingAs($user)->post(route('payments.store'), [
            'bill_ids' => $ids,
            'tanggal_bayar' => '2025-03-10',
            'metode_bayar' => 'transfer',
            'nominal_bayar' => 700000,
        ])->assertRedirect();

        $payment = Payment::firstOrFail();
        $this->assertSame(4, $payment->bills()->count());
        $this->assertSame(700000.0, (float) $payment->nominal_bayar);
        $this->assertSame(4, Bill::whereIn('id', $ids)->where('status', 'lunas')->count());

        $this->actingAs($user)->delete(route('payments.destroy', $payment))->assertRedirect();
        $this->assertSame(4, Bill::whereIn('id', $ids)->where('status', 'belum_bayar')->count());
    }

    public function test_grouped_monthly_bills_do_not_send_paid_months_to_payment_form(): void
    {
        ['user' => $user, 'bill' => $january] = $this->paymentFixture();
        $this->actingAs($user)->post(route('payments.store'), [
            'bill_ids' => [$january->id],
            'tanggal_bayar' => '2025-01-10',
            'metode_bayar' => 'tunai',
            'nominal_bayar' => 200000,
        ])->assertRedirect();

        $february = Bill::create([
            'institution_id' => $january->institution_id,
            'school_year_id' => $january->school_year_id,
            'student_id' => $january->student_id,
            'payment_type_id' => $january->payment_type_id,
            'bulan' => 2,
            'tahun' => 2025,
            'nominal' => 200000,
            'status' => 'belum_bayar',
        ]);

        $this->actingAs($user)->get(route('bills.index'))
            ->assertOk()
            ->assertSee(route('payments.create', ['bill_ids' => [$february->id]]), false)
            ->assertDontSee(route('payments.create', ['bill_ids' => [$january->id, $february->id]]), false);
    }
}
