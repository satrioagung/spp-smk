<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $bill = $payment->bill;
        if ($bill) {
            $bill->updateStatus()->save();
        }
    }

    public function deleted(Payment $payment): void
    {
        $payment->loadMissing('bills');
        if ($payment->bills->isNotEmpty()) {
            foreach ($payment->bills as $bill) {
                $bill->nominal_terbayar = max(0, (float) $bill->nominal_terbayar - (float) $bill->pivot->nominal_bayar);
                $bill->updateStatus()->save();
            }

            return;
        }

        // Kompatibilitas untuk transaksi lama yang belum memiliki alokasi pivot.
        $bill = $payment->bill;
        if ($bill) {
            $bill->nominal_terbayar = max(0, (float) $bill->nominal_terbayar - (float) $payment->nominal_bayar);
            $bill->updateStatus()->save();
        }
    }
}
