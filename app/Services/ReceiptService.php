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
        $data = $this->receiptViewData($payment, $template);
        $template = $data['template'];
        $view = $this->getTemplateView((string) ($template->ukuran ?: 'a4'));
        $pdf = DomPdf::loadView($view, $data);

        $billCount = max(1, $payment->bills->count());
        $thermalHeight = max(380, 285 + ($billCount * 40));
        if ($template->ukuran === 'thermal58') {
            $pdf->setPaper([0, 0, 164, max(430, $thermalHeight + 50)], 'portrait');
        } elseif ($template->ukuran === 'thermal80') {
            $pdf->setPaper([0, 0, 226, $thermalHeight], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        return $pdf;
    }

    public function print(Payment $payment, ?ReceiptTemplate $template = null, ?string $androidThermerUrl = null, ?string $iosThermerUrl = null): \Illuminate\View\View
    {
        $data = $this->receiptViewData($payment, $template);
        $data['androidThermerUrl'] = $androidThermerUrl;
        $data['iosThermerUrl'] = $iosThermerUrl;

        return view('receipts.print', $data);
    }

    public function thermerPayload(Payment $payment, string $baseUrl, ?ReceiptTemplate $template = null): array
    {
        $payment->load(['institution', 'student.schoolClass', 'bill.paymentType', 'bills.paymentType', 'petugas']);
        $institution = $payment->institution;
        $template ??= ReceiptTemplate::active()->where('institution_id', $payment->institution_id)->latest()->first();

        $payload = [];
        $columns = $template?->ukuran === 'thermal58' ? 32 : 48;
        $separator = str_repeat('-', $columns);
        // Struk thermal dicetak tanpa logo agar hemat kertas dan konsisten.
        $logoStoragePath = null;
        $logoPath = $logoStoragePath ? public_path('storage/' . $logoStoragePath) : null;

        if ($logoPath && is_file($logoPath)) {
            $payload[] = [
                'type' => 1,
                'path' => rtrim($baseUrl, '/') . '/storage/' . ltrim($logoStoragePath, '/'),
                'align' => 1,
            ];
        }

        $payload[] = ['type' => 0, 'content' => (string) ($institution?->nama ?: 'BUKTI PEMBAYARAN'), 'bold' => 1, 'align' => 1, 'format' => 2];
        $payload[] = ['type' => 0, 'content' => (string) ($institution?->alamat ?: ''), 'bold' => 0, 'align' => 1, 'format' => 0];
        $payload[] = ['type' => 0, 'content' => $separator, 'bold' => 0, 'align' => 1, 'format' => 0];
        $payload[] = ['type' => 0, 'content' => 'Transaksi: ' . $payment->nomor_transaksi, 'bold' => 0, 'align' => 0, 'format' => 0];
        $payload[] = ['type' => 0, 'content' => 'Tanggal: ' . $payment->tanggal_bayar?->format('d/m/Y'), 'bold' => 0, 'align' => 0, 'format' => 0];
        $payload[] = ['type' => 0, 'content' => 'Siswa: ' . $payment->student?->nama_lengkap, 'bold' => 0, 'align' => 0, 'format' => 0];
        $payload[] = ['type' => 0, 'content' => 'NIS/Kelas: ' . $payment->student?->nis . ' / ' . $payment->student?->schoolClass?->nama_kelas, 'bold' => 0, 'align' => 0, 'format' => 0];
        $payload[] = ['type' => 0, 'content' => 'Metode: ' . $payment->metode_bayar_label, 'bold' => 0, 'align' => 0, 'format' => 0];
        $payload[] = ['type' => 0, 'content' => $separator, 'bold' => 0, 'align' => 1, 'format' => 0];

        // Baris item: semua rata kiri dalam satu baris.
        if ($payment->bills->isNotEmpty()) {
            foreach ($payment->bills as $bill) {
                $period = $bill->bulan ? Carbon::create()->month($bill->bulan)->translatedFormat('F') . ' ' . $bill->tahun : (string) $bill->tahun;
                $amount = 'Rp ' . number_format((float) $bill->pivot->nominal_bayar, 0, ',', '.');
                $payload[] = ['type' => 0, 'content' => ($bill->paymentType?->nama ?: 'Pembayaran') . ' - ' . $period . ' ' . $amount, 'bold' => 0, 'align' => 0, 'format' => 0];
            }
        } else {
            $amount = 'Rp ' . number_format((float) $payment->nominal_bayar, 0, ',', '.');
            $payload[] = ['type' => 0, 'content' => ($payment->bill?->paymentType?->nama ?: 'Pembayaran') . ' ' . $amount, 'bold' => 0, 'align' => 0, 'format' => 0];
        }

        $payload[] = ['type' => 0, 'content' => $separator, 'bold' => 0, 'align' => 1, 'format' => 0];
        $payload[] = ['type' => 0, 'content' => 'TOTAL: Rp ' . number_format((float) $payment->nominal_bayar, 0, ',', '.'), 'bold' => 1, 'align' => 2, 'format' => 3];
        $payload[] = ['type' => 0, 'content' => 'Terima kasih atas pembayaran Anda.', 'bold' => 0, 'align' => 1, 'format' => 0];

        return $payload;
    }

    private function receiptViewData(Payment $payment, ?ReceiptTemplate $template = null): array
    {
        $payment->load(['institution', 'student.schoolClass', 'bill.paymentType', 'bills.paymentType', 'petugas']);
        $template ??= new ReceiptTemplate([
            'header_text' => "BUKTI PEMBAYARAN\n{{ school_name }}\n{{ school_address }}",
            'footer_text' => "Terima kasih atas pembayaran Anda.\nDitandatangani oleh: {{ petugas }}",
            'show_logo' => true,
            'show_qr' => false,
            'ukuran' => 'a4',
        ]);

        $isThermal = in_array($template->ukuran, ['thermal58', 'thermal80'], true);
        $logoStoragePath = $isThermal ? null : $payment->institution?->logo;

        return [
            'payment' => $payment,
            'template' => $template,
            'headerText' => $this->renderPlaceholders((string) $template->header_text, $payment),
            'footerText' => $this->renderPlaceholders((string) $template->footer_text, $payment),
            'logoPath' => $logoStoragePath ? public_path('storage/' . $logoStoragePath) : null,
            'logoUrl' => $logoStoragePath ? '/storage/' . ltrim($logoStoragePath, '/') : null,
            'logoExists' => $logoStoragePath ? is_file(public_path('storage/' . $logoStoragePath)) : false,
        ];
    }

    private function renderPlaceholders(string $text, Payment $payment): string
    {
        $bills = $payment->bills;
        $periods = $bills->isNotEmpty()
            ? $bills->map(fn($bill): string => $bill->bulan ? Carbon::create()->month($bill->bulan)->translatedFormat('F') . ' ' . $bill->tahun : (string) $bill->tahun)->join(', ')
            : ($payment->bill?->bulan ? Carbon::create()->month($payment->bill->bulan)->translatedFormat('F') . ' ' . $payment->bill->tahun : (string) ($payment->bill?->tahun ?: '-'));
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
            '{{ amount }}' => 'Rp ' . number_format((float) $payment->nominal_bayar, 0, ',', '.'),
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
