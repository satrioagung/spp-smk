@php
    $paymentBills = $payment->bills->isNotEmpty() ? $payment->bills : collect([$payment->bill]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Detail Pembayaran</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $payment->nomor_transaksi }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
        <div class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm text-slate-500">Nomor Transaksi</p>
                <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $payment->nomor_transaksi }}</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Dicatat oleh {{ $payment->petugas?->name }} pada {{ $payment->tanggal_bayar?->translatedFormat('d F Y') }}
                </p>
            </div>
            <a href="{{ route('payments.struk', $payment) }}" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">Cetak Struk</a>
        </div>

        <dl class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <dt class="text-xs text-slate-500">Siswa</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $payment->student?->nama_lengkap }} ({{ $payment->student?->nis }})</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Kelas</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $payment->student?->schoolClass?->nama_kelas }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Jenis Pembayaran</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $payment->payment_types_label }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Metode</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $payment->metode_bayar_label }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Total Nominal Bayar</dt>
                <dd class="mt-1 text-lg font-bold text-emerald-700">Rp {{ number_format((float) $payment->nominal_bayar, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Jumlah Tagihan</dt>
                <dd class="mt-1 font-semibold text-slate-800">{{ $paymentBills->count() }} tagihan</dd>
            </div>
        </dl>

        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200">
            <div class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">Rincian Alokasi Pembayaran</div>
            <div class="divide-y divide-slate-100">
                @foreach ($paymentBills as $bill)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-slate-800">{{ $bill->paymentType?->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $bill->bulan ? \Carbon\Carbon::create()->month($bill->bulan)->translatedFormat('F').' ' : '' }}{{ $bill->tahun }}</p>
                        </div>
                        <span class="whitespace-nowrap font-semibold text-slate-700">Rp {{ number_format((float) ($bill->pivot?->nominal_bayar ?? $payment->nominal_bayar), 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($payment->keterangan)
            <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600"><strong>Keterangan:</strong> {{ $payment->keterangan }}</div>
        @endif

        <div class="mt-8 flex flex-wrap justify-end gap-3">
            @if ($payment->bukti_file)
                <a href="{{ asset('storage/'.$payment->bukti_file) }}" target="_blank" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">Lihat Bukti</a>
            @endif
            @if (auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
                <form method="POST" action="{{ route('payments.destroy', $payment) }}">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-xl bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700">Hapus Pembayaran</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
