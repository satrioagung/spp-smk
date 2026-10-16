<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold leading-tight text-slate-900">
            Dashboard
        </h2>
    </x-slot>

    <div class="space-y-8">
        <div><p class="text-slate-600">Selamat datang di SIP-SPP.</p><p class="mt-1 text-sm text-slate-500">{{ $activeInstitution?->nama ?: 'Semua lembaga' }}</p></div>
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5"><div class="flex items-center justify-between"><p class="text-sm font-medium text-blue-700">Total Siswa</p><svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m4-8a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm6 2a3 3 0 1 1-6 0"/></svg></div><p class="mt-5 text-3xl font-bold text-blue-900">{{ number_format($stats['total_siswa'], 0, ',', '.') }}</p></div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5"><div class="flex items-center justify-between"><p class="text-sm font-medium text-emerald-700">Total Tagihan</p><svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14h6m-8 4h10M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/></svg></div><p class="mt-5 text-2xl font-bold text-emerald-900">Rp {{ number_format($stats['total_tagihan'], 0, ',', '.') }}</p></div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5"><div class="flex items-center justify-between"><p class="text-sm font-medium text-amber-700">Total Pembayaran</p><svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v8m-4-4h8m7 0a11 11 0 1 1-22 0 11 11 0 0 1 22 0Z"/></svg></div><p class="mt-5 text-2xl font-bold text-amber-900">Rp {{ number_format($stats['total_pembayaran'], 0, ',', '.') }}</p></div>
            <div class="rounded-2xl border border-violet-100 bg-violet-50 p-5"><div class="flex items-center justify-between"><p class="text-sm font-medium text-violet-700">Saldo Kas</p><svg class="h-6 w-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M5 6h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Zm11 8h.01"/></svg></div><p class="mt-5 text-2xl font-bold text-violet-900">Rp {{ number_format($stats['saldo_kas'], 0, ',', '.') }}</p></div>
            <div class="rounded-2xl border border-red-100 bg-red-50 p-5"><div class="flex items-center justify-between"><p class="text-sm font-medium text-red-700">Total Tunggakan</p><svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 4.8 2.9 18a2 2 0 0 0 1.75 3h14.7a2 2 0 0 0 1.75-3l-7.4-13.2a2 2 0 0 0-3.5 0Z"/></svg></div><p class="mt-5 text-2xl font-bold text-red-900">Rp {{ number_format($stats['total_tunggakan'], 0, ',', '.') }}</p></div>
        </div>
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-100"><div class="border-b border-slate-100 px-6 py-5"><h3 class="font-semibold text-slate-900">Transaksi Terakhir</h3></div><div class="p-10 text-center text-sm text-slate-500">Belum ada transaksi</div></section>
            <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-100"><div class="border-b border-slate-100 px-6 py-5"><h3 class="font-semibold text-slate-900">Tagihan Jatuh Tempo</h3></div><div class="p-10 text-center text-sm text-slate-500">Tidak ada tagihan jatuh tempo</div></section>
        </div>
    </div>
            </div>
        </div>
    </div>
</x-app-layout>
