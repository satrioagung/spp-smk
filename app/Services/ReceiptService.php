<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ReceiptTemplate;
use Barryvdh\DomPDF\PDF;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Carbon\Carbon;

class ReceiptService
{
    public function generate(Payment $payment, ?ReceiptTemplate $template = null): PDF
    {
        $payment->load(['institution', 'student.schoolClass', 'bill.paymentType', 'bills.paymentType', 'petugas']);
        $template ??= new ReceiptTemplate([
            'header_text' => "BUKTI PEMBAYARAN\n{{ school_name }}\n{{ school_address }}",
            'footer_text' => "Terima kasih atas pembayaran Anda.\nDitandatangani oleh: {{ petugas }}",
            'show_logo' => true,
            'show_qr' => false,
            'ukuran' => 'a4',
        ]);

        $view = $this->getTemplateView((string) ($template->ukuran ?: 'a4'));
        $pdf = DomPdf::loadView($view, [
            'payment' => $payment,
            'template' => $template,
            'headerText' => $this->renderPlaceholders((string) $template->header_text, $payment),
            'footerText' => $this->renderPlaceholders((string) $template->footer_text, $payment),
            'logoPath' => $payment->institution->logo ? public_path('storage/'.$payment->institution->logo) : null,
        ]);

        if ($template->ukuran === 'thermal58') {
            $pdf->setPaper([0, 0, 164, 600], 'portrait');
        } elseif ($template->ukuran === 'thermal80') {
            $pdf->setPaper([0, 0, 226, 700], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        return $pdf;
    }

    private function renderPlaceholders(string $text, Payment $payment): string
    {
        $bills = $payment->bills;
        $periods = $bills->isNotEmpty()
            ? $bills->map(fn ($bill): string => $bill->bulan ? Carbon::create()->month($bill->bulan)->translatedFormat('F').' '.$bill->tahun : (string) $bill->tahun)->join(', ')
            : ($payment->bill?->bulan ? Carbon::create()->month($payment->bill->bulan)->translatedFormat('F').' '.$payment->bill->tahun : (string) ($payment->bill?->tahun ?: '-'));
        $paymentTypes = $bills->isNotEmpty()
            ? $bills->pluck('paymentType.nama')->filter()->unique()->join(', ')
            : ($payment->bill?->paymentType?->nama ?: '-');
        $values = [
            '{{ school_name }}' => $payment->institution?->nama ?: '-',
            '{{ school_address }}' => $payment->institution?->alamat ?: '-',
            '{{ student_name }}' => $payment->student?->nama_lengkap ?: '-',
            '{{ student_nis }}' => $payment->student?->nis ?: '-',
            '{{ student_class }}' => $payment->student?->schoolClass?->nama_kelas ?: '-',
            '{{ payment_type }}' => $paymentTypes ?: '-',
            '{{ payment_period }}' => $periods ?: '-',
            '{{ amount }}' => 'Rp '.number_format((float) $payment->nominal_bayar, 0, ',', '.'),
            '{{ payment_date }}' => $payment->tanggal_bayar?->translatedFormat('d F Y') ?: '-',
            '{{ nomor_transaksi }}' => $payment->nomor_transaksi,
            '{{ petugas }}' => $payment->petugas?->name ?: '-',
        ];

        return nl2br(e(strtr($text, $values)));
    }

    private function getTemplateView(string $ukuran): string
    {
        return in_array($ukuran, ['thermal58', 'thermal80'], true) ? 'receipts.thermal' : 'receipts.a4';
    }
}
