@php
    $activeInstitution = session('active_institution_id')
        ? \App\Models\Institution::find(session('active_institution_id'))
        : auth()->user()?->institution;
    $tenantOptions = auth()->user()?->hasRole('super_admin')
        ? \App\Models\Institution::active()->orderBy('nama')->get()
        : collect();
    $themeColor = $activeInstitution?->warna_tema ?: '#1d4ed8';
@endphp
<!DOCTYPE html>
<html lang="id" style="--warna-tema: {{ $themeColor }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SIP-SPP') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body x-data="{ sidebarOpen: false }" class="bg-slate-50 font-sans antialiased">
        <div class="min-h-screen lg:flex">
            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-slate-900/40 lg:hidden"></div>
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-30 flex w-72 flex-col bg-slate-950 text-white transition-transform duration-200 lg:static lg:shrink-0">
                <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--warna-tema)] text-sm font-bold">SP</div>
                    <div><div class="font-bold tracking-wide">SIP-SPP</div><div class="text-xs text-slate-400">Pembayaran sekolah</div></div>
                </div>
                <nav class="flex-1 space-y-1 px-4 py-6 text-sm">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Dashboard</a>
                    @role('super_admin')
                        <a href="{{ route('institutions.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('institutions.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Data Lembaga</a>
                    @endrole
                    @hasanyrole('admin_sekolah|bendahara')
                        <a href="{{ route('students.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('students.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Data Siswa</a>
                        <a href="{{ route('classes.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('classes.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Data Kelas</a>
                        <a href="{{ route('school-years.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('school-years.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Tahun Ajaran</a>
                        <a href="{{ route('payment-types.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('payment-types.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Jenis Pembayaran</a>
                        <a href="{{ route('bills.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('bills.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Tagihan</a>
                    @endhasanyrole
                    @hasanyrole('admin_sekolah|bendahara')
                        <a href="{{ route('payments.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('payments.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Riwayat Pembayaran</a>
                        <a href="{{ route('receipt-templates.index') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 {{ request()->routeIs('receipt-templates.*') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">Template Struk</a>
                    @endhasanyrole
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-400 hover:bg-white/5 hover:text-white">Kas Keuangan</a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-400 hover:bg-white/5 hover:text-white">Laporan</a>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-4 py-3 text-slate-400 hover:bg-white/5 hover:text-white">Pengaturan</a>
                </nav>
                <div class="border-t border-white/10 p-4 text-xs text-slate-500">Versi Modul 1 · SIP-SPP</div>
            </aside>
            <div class="min-w-0 flex-1">
                <header class="flex h-20 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-8">
                    <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Buka menu">☰</button>
                    <div class="hidden sm:block"><p class="text-sm font-semibold text-slate-800">{{ $activeInstitution?->nama ?: 'Semua Lembaga' }}</p><p class="text-xs text-slate-500">Lembaga aktif</p></div>
                    <div class="ml-auto flex items-center gap-3">
                        @if($tenantOptions->isNotEmpty())
                            <form method="POST" action="{{ $activeInstitution ? route('switch.tenant', $activeInstitution) : '#' }}" class="hidden items-center gap-2 md:flex">
                                @csrf
                                <label for="active_institution" class="sr-only">Pilih lembaga aktif</label>
                                <select id="active_institution" name="institution" onchange="this.form.action = '{{ url('/switch-lembaga') }}/' + this.value; this.form.submit()" class="rounded-lg border-slate-200 py-2 pl-3 pr-8 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($tenantOptions as $option)
                                        <option value="{{ $option->id }}" @selected($activeInstitution?->id === $option->id)>{{ $option->nama }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="text-right hover:opacity-80"><p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p><p class="text-xs text-slate-500">{{ auth()->user()->getRoleNames()->first() ?: 'Pengguna' }} · Edit profil</p></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Keluar</button></form>
                    </div>
                </header>
                @isset($header)<div class="border-b border-slate-200 bg-white px-4 py-6 sm:px-8">{{ $header }}</div>@endisset
                <main class="p-4 sm:p-8">
                    @if(session('success'))
                        <div x-data="{ tampil: true }" x-show="tampil" x-transition class="mb-6 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            <span>{{ session('success') }}</span><button type="button" @click="tampil = false" class="text-lg leading-none">&times;</button>
                        </div>
                    @endif
                    @if($errors->any())
                        <div x-data="{ tampil: true }" x-show="tampil" x-transition class="mb-6 flex items-center justify-between rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <span>{{ $errors->first() }}</span><button type="button" @click="tampil = false" class="text-lg leading-none">&times;</button>
                        </div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
