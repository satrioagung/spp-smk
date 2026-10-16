<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $payment->nomor_transaksi }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #1f2937; font-family: Arial, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: 10px; padding: 18px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 8px; padding: 10px 16px; color: white; background: #2563eb; font-size: 14px; text-decoration: none; cursor: pointer; }
        .toolbar a.android { background: #16a34a; }
        .toolbar a.ios { background: #7c3aed; }
        .toolbar a { background: #475569; }
        .receipt { width: 210mm; min-height: 270mm; margin: 0 auto 24px; padding: 18mm; background: white; }
        .receipt.thermal58 { width: 58mm; min-height: auto; padding: 5mm; font-size: 10px; }
        .receipt.thermal80 { width: 80mm; min-height: auto; padding: 5mm; font-size: 10px; }
        .header { text-align: center; border-bottom: 2px solid #1f2937; padding-bottom: 14px; }
        .logo { max-width: 70px; max-height: 70px; margin-bottom: 8px; }
        .title { font-size: 18px; font-weight: bold; line-height: 1.45; }
        .thermal58 .title, .thermal80 .title { font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        .meta { margin-top: 22px; }
        .meta td { padding: 5px 0; vertical-align: top; }
        .meta td:first-child { width: 32%; color: #64748b; }
        .detail { margin-top: 22px; }
        .detail th, .detail td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; }
        .detail th { background: #f1f5f9; }
        .total { margin-top: 18px; text-align: right; font-size: 16px; font-weight: bold; }
        .footer { margin-top: 38px; padding-top: 14px; border-top: 1px solid #cbd5e1; text-align: center; color: #475569; line-height: 1.5; }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .receipt { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('payments.struk.pdf', $payment) }}" target="_blank" rel="noopener">PDF</a>
        @if ($androidThermerUrl)
            <a href="{{ $androidThermerUrl }}" class="android">Cetak Android</a>
        @endif
        @if ($iosThermerUrl)
            <a href="{{ $iosThermerUrl }}" class="ios">Cetak iOS</a>
        @endif
        <a href="{{ route('payments.show', $payment) }}">Kembali</a>
    </div>

    <main class="receipt {{ $template->ukuran ?: 'a4' }}">
        <div class="header">
            @if ($template->show_logo && $logoUrl && $logoExists)
                <img class="logo" src="{{ $logoUrl }}" alt="Logo">
            @endif
            <div class="title">{!! $headerText !!}</div>
        </div>

        <table class="meta">
            <tr><td>Nomor Transaksi</td><td><strong>{{ $payment->nomor_transaksi }}</strong></td></tr>
            <tr><td>Tanggal</td><td>{{ $payment->tanggal_bayar?->translatedFormat('d F Y') }}</td></tr>
            <tr><td>Petugas</td><td>{{ $payment->petugas?->name }}</td></tr>
            <tr><td>Siswa</td><td>{{ $payment->student?->nama_lengkap }} ({{ $payment->student?->nis }})</td></tr>
            <tr><td>Kelas</td><td>{{ $payment->student?->schoolClass?->nama_kelas }}</td></tr>
        </table>

        <table class="detail">
            <thead><tr><th>Pembayaran</th><th>Periode</th><th style="text-align:right">Nominal</th></tr></thead>
            <tbody>
                @forelse ($payment->bills as $bill)
                    <tr>
                        <td>{{ $bill->paymentType?->nama }}</td>
                        <td>{{ $bill->bulan ? \Carbon\Carbon::create()->month($bill->bulan)->translatedFormat('F').' ' : '' }}{{ $bill->tahun }}</td>
                        <td style="text-align:right">Rp {{ number_format((float) $bill->pivot->nominal_bayar, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td>{{ $payment->bill?->paymentType?->nama }}</td>
                        <td>{{ $payment->bill?->bulan ? \Carbon\Carbon::create()->month($payment->bill->bulan)->translatedFormat('F').' ' : '' }}{{ $payment->bill?->tahun }}</td>
                        <td style="text-align:right">Rp {{ number_format((float) $payment->nominal_bayar, 0, ',', '.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="total">Total Dibayar: Rp {{ number_format((float) $payment->nominal_bayar, 0, ',', '.') }}</div>
        <div class="footer">{!! $footerText !!}</div>
    </main>

    <script>
        // Di komputer, halaman ini tetap nyaman dipakai untuk mencetak dari
        // browser. Pada HP, gunakan tombol Android atau iOS agar Thermer
        // menerima data thermal langsung dalam format ESC/POS.
        window.addEventListener('load', () => {
            if (!window.matchMedia('(pointer: coarse)').matches) {
                window.print();
            }
        });
    </script>
</body>
</html>
