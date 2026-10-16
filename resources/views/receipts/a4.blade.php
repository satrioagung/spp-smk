<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $payment->nomor_transaksi }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; margin: 36px; }
        .header { text-align: center; border-bottom: 2px solid #1f2937; padding-bottom: 18px; }
        .logo { max-width: 70px; max-height: 70px; float: left; margin-right: 15px; }
        .title { font-size: 18px; font-weight: bold; }
        .meta { margin-top: 28px; width: 100%; border-collapse: collapse; }
        .meta td { padding: 7px 0; vertical-align: top; }
        .meta td:first-child { width: 28%; color: #6b7280; }
        .detail { margin-top: 25px; border-collapse: collapse; width: 100%; }
        .detail th, .detail td { border: 1px solid #d1d5db; padding: 9px; text-align: left; }
        .detail th { background: #f3f4f6; }
        .total { text-align: right; font-size: 16px; font-weight: bold; margin-top: 22px; }
        .footer { margin-top: 45px; border-top: 1px solid #d1d5db; padding-top: 18px; text-align: center; color: #4b5563; }
    </style>
</head>
<body>
    <div class="header">
        @if ($template->show_logo && $logoPath && is_file($logoPath))
            <img class="logo" src="{{ $logoPath }}">
        @endif
        <div class="title">{!! $headerText !!}</div>
    </div>

    <table class="meta">
        <tr><td>Nomor Transaksi</td><td><strong>{{ $payment->nomor_transaksi }}</strong></td></tr>
        <tr><td>Tanggal Pembayaran</td><td>{{ $payment->tanggal_bayar?->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Petugas</td><td>{{ $payment->petugas?->name }}</td></tr>
    </table>

    <table class="detail">
        <thead><tr><th>Deskripsi Pembayaran</th><th>Periode</th><th style="text-align:right">Nominal</th></tr></thead>
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

    <table class="meta">
        <tr><td>Nama Siswa</td><td>{{ $payment->student?->nama_lengkap }}</td></tr>
        <tr><td>NIS / Kelas</td><td>{{ $payment->student?->nis }} / {{ $payment->student?->schoolClass?->nama_kelas }}</td></tr>
        <tr><td>Metode</td><td>{{ $payment->metode_bayar_label }}</td></tr>
    </table>

    <div class="total">Total Dibayar: Rp {{ number_format((float) $payment->nominal_bayar, 0, ',', '.') }}</div>
    <div class="footer">{!! $footerText !!}</div>
</body>
</html>
