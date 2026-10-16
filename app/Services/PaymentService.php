<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Institution;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function bayar(Bill $bill, array $data): Payment
    {
        return $this->bayarBanyak(collect([$bill]), $data);
    }

    /**
     * Catat satu transaksi dan alokasikan nominalnya ke beberapa tagihan.
     * Alokasi dilakukan berurutan sesuai urutan tagihan yang dipilih.
     */
    public function bayarBanyak(Collection $bills, array $data): Payment
    {
        return DB::transaction(function () use ($bills, $data): Payment {
            $billIds = $bills->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
            $lockedBills = Bill::query()->whereIn('id', $billIds)->lockForUpdate()->get()->sortBy(
                fn (Bill $bill): int => array_search($bill->id, $billIds, true)
            )->values();

            if ($lockedBills->isEmpty() || $lockedBills->count() !== count($billIds)) {
                throw ValidationException::withMessages(['bill_ids' => 'Tagihan yang dipilih tidak ditemukan.']);
            }

            $amount = round((float) $data['nominal_bayar'], 2);
            $remainingTotal = round($lockedBills->sum(fn (Bill $bill): float => $bill->sisa_tagihan), 2);
            if ($amount <= 0 || $amount > $remainingTotal) {
                throw ValidationException::withMessages(['nominal_bayar' => 'Nominal pembayaran tidak boleh melebihi total sisa tagihan yang dipilih.']);
            }

            $firstBill = $lockedBills->first();
            $institution = Institution::findOrFail($firstBill->institution_id);
            $payment = Payment::create([
                // bill_id dipertahankan sebagai tagihan utama untuk kompatibilitas data Modul 5.
                'institution_id' => $firstBill->institution_id,
                'bill_id' => $firstBill->id,
                'student_id' => $firstBill->student_id,
                'nomor_transaksi' => $this->generateNomorTransaksi($institution),
                'tanggal_bayar' => $data['tanggal_bayar'],
                'metode_bayar' => $data['metode_bayar'],
                'nominal_bayar' => $amount,
                'petugas_id' => $data['petugas_id'],
                'bukti_file' => $data['bukti_file'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            $remainingAmount = $amount;
            foreach ($lockedBills as $bill) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $allocation = min($remainingAmount, $bill->sisa_tagihan);
                if ($allocation <= 0) {
                    continue;
                }

                $payment->bills()->attach($bill->id, ['nominal_bayar' => $allocation]);
                $bill->nominal_terbayar = (float) $bill->nominal_terbayar + $allocation;
                $bill->updateStatus()->save();
                $remainingAmount = round($remainingAmount - $allocation, 2);
            }

            return $payment->load(['bills.paymentType']);
        });
    }

    public function generateNomorTransaksi(Institution $institution): string
    {
        $year = now()->year;
        $prefix = strtoupper(trim($institution->prefix_nomor_struk ?: 'SPP'));
        $base = "{$prefix}/TRX/{$year}/";
        $lastNumber = Payment::withTrashed()->where('institution_id', $institution->id)->where('nomor_transaksi', 'like', $base.'%')->get()->map(function (Payment $payment) use ($base): int {
            return (int) str_replace($base, '', $payment->nomor_transaksi);
        })->max() ?: 0;

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $number = $base.str_pad((string) ($lastNumber + $attempt), 5, '0', STR_PAD_LEFT);
            if (! Payment::withTrashed()->where('nomor_transaksi', $number)->exists()) {
                return $number;
            }
        }

        return $base.str_pad((string) (microtime(true) * 1000 % 100000), 5, '0', STR_PAD_LEFT);
    }

    public function updateStatusTagihan(Bill $bill): void
    {
        $bill->updateStatus()->save();
    }
}
