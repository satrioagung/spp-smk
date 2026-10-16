<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div><h2 class="text-xl font-bold text-slate-900">Data Lembaga</h2><p class="mt-1 text-sm text-slate-500">Kelola data sekolah dan konfigurasi struk pembayaran.</p></div>
            <a href="{{ route('institutions.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">+ Tambah Lembaga</a>
        </div>
    </x-slot>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-4">Lembaga</th><th class="px-6 py-4">Jenjang</th><th class="px-6 py-4">Kontak</th><th class="px-6 py-4">Akun Admin</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($institutions as $institution)
                        <tr x-data="{ konfirmasi: false }" class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-6 py-4"><div class="flex items-center gap-3">@if($institution->logo)<img src="{{ asset('storage/'.$institution->logo) }}" alt="Logo {{ $institution->nama }}" class="h-10 w-10 rounded-lg object-cover">@else<div class="flex h-10 w-10 items-center justify-center rounded-lg text-xs font-bold text-white" style="background-color: {{ $institution->warna_tema }}">{{ strtoupper(substr($institution->nama, 0, 2)) }}</div>@endif<div><p class="font-semibold text-slate-900">{{ $institution->nama }}</p><p class="text-xs text-slate-500">{{ $institution->prefix_nomor_struk }}</p></div></div></td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ $institution->jenjang }}</td>
                            <td class="px-6 py-4 text-slate-600"><div>{{ $institution->telepon ?: '-' }}</div><div class="text-xs">{{ $institution->email ?: '-' }}</div></td>
                            <td class="px-6 py-4 text-slate-600"><div class="font-medium text-slate-700">{{ $institution->adminAccount?->name ?: '-' }}</div><div class="text-xs">{{ $institution->adminAccount?->email ?: 'Belum tersedia' }}</div></td>
                            <td class="whitespace-nowrap px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $institution->aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $institution->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="whitespace-nowrap px-6 py-4 text-right"><div class="flex justify-end gap-2"><form method="POST" action="{{ route('institutions.toggle', $institution) }}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">{{ $institution->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form><a href="{{ route('institutions.edit', $institution) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">Edit</a><button type="button" @click="konfirmasi = true" class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-100">Hapus</button></div><template x-teleport="body"><div x-show="konfirmasi" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" @keydown.escape.window="konfirmasi = false"><div @click.outside="konfirmasi = false" class="w-full max-w-md rounded-2xl bg-white p-6 text-left shadow-xl"><h3 class="text-lg font-bold text-slate-900">Hapus lembaga?</h3><p class="mt-2 text-sm text-slate-500">Data {{ $institution->nama }} akan dihapus. Tindakan ini dapat dipulihkan oleh administrator.</p><div class="mt-6 flex justify-end gap-3"><button type="button" @click="konfirmasi = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600">Batal</button><form method="POST" action="{{ route('institutions.destroy', $institution) }}">@csrf @method('DELETE')<button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white">Hapus</button></form></div></div></div></template></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada data lembaga.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($institutions->hasPages())<div class="flex items-center justify-between border-t border-slate-100 px-6 py-4 text-sm"><span class="text-slate-500">Menampilkan {{ $institutions->firstItem() }}–{{ $institutions->lastItem() }} dari {{ $institutions->total() }} lembaga</span><div class="flex gap-2">@if($institutions->onFirstPage())<span class="rounded-lg border border-slate-100 px-3 py-2 text-slate-300">Sebelumnya</span>@else<a href="{{ $institutions->previousPageUrl() }}" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-600">Sebelumnya</a>@endif @if($institutions->hasMorePages())<a href="{{ $institutions->nextPageUrl() }}" class="rounded-lg border border-slate-200 px-3 py-2 text-slate-600">Berikutnya</a>@else<span class="rounded-lg border border-slate-100 px-3 py-2 text-slate-300">Berikutnya</span>@endif</div></div>@endif
    </div>
</x-app-layout>
