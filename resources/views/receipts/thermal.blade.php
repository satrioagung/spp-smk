<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $payment->nomor_transaksi }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; width: 100%; margin: 0; color: #111; }
        .receipt { text-align: center; padding: 10px; }
        .logo { max-width: 45px; max-height: 45px; }
        .line { border-top: 1px dashed #333; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; text-align: left; margin: 6px 0; }
        .amount { font-size: 14px; font-weight: bold; text-align: right; margin-top: 12px; }
        .footer { margin-top: 18px; white-space: normal; }
    </style>
</head>
<body>
    <div class="receipt">
        @if ($template->show_logo && $logoPath && is_file($logoPath))
            <img class="logo" src="{{ $logoPath }}"><br>
        @endif
        <div>{!! $headerText !!}</div>
        <div class="line"></div>
        <div class="row"><span>Transaksi</span><strong>{{ $payment->nomor_transaksi }}</strong></div>
        <div class="row"><span>Tanggal</span><span>{{ $payment->tanggal_bayar?->format('d/m/Y') }}</span></div>
        <div class="row"><span>Siswa</span><span>{{ $payment->student?->nama_lengkap }}</span></div>
        <div class="row"><span>NIS/Kelas</span><span>{{ $payment->student?->nis }} / {{ $payment->student?->schoolClass?->nama_kelas }}</span></div>
        <div class="line"></div>

        @forelse ($payment->bills as $bill)
            <div class="row">
                <span>{{ $bill->paymentType?->nama }}<br>{{ $bill->bulan ? \Carbon\Carbon::create()->month($bill->bulan)->translatedFormat('F') : $bill->tahun }}</span>
                <span>Rp {{ number_format((float) $bill->pivot->nominal_bayar, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="row"><span>{{ $payment->bill?->paymentType?->nama }}</span><span>Rp {{ number_format((float) $payment->nominal_bayar, 0, ',', '.') }}</span></div>
        @endforelse

        <div class="amount">Total: Rp {{ number_format((float) $payment->nominal_bayar, 0, ',', '.') }}</div>
        <div class="line"></div>
        <div class="footer">{!! $footerText !!}</div>
    </div>
</body>
</html>
