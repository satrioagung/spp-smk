<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div><h2 class="text-xl font-bold text-slate-900">Tahun Ajaran</h2><p class="mt-1 text-sm text-slate-500">Atur periode akademik yang digunakan untuk data sekolah.</p></div>
            <a href="{{ route('school-years.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">+ Tambah Tahun Ajaran</a>
        </div>
    </x-slot>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-4">Nama</th><th class="px-6 py-4">Periode</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schoolYears as $schoolYear)
                        <tr x-data="{ konfirmasi: false }" class="hover:bg-slate-50/70">
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $schoolYear->nama }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ $schoolYear->tanggal_mulai->translatedFormat('d M Y') }} – {{ $schoolYear->tanggal_selesai->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $schoolYear->aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $schoolYear->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="whitespace-nowrap px-6 py-4 text-right"><div class="flex justify-end gap-2">
                                @if(!$schoolYear->aktif)<form method="POST" action="{{ route('school-years.aktif', $schoolYear) }}">@csrf<button class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50">Jadikan Aktif</button></form>@endif
                                <a href="{{ route('school-years.edit', $schoolYear) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">Edit</a>
                                <button type="button" @click="konfirmasi = true" class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-100">Hapus</button>
                            </div><template x-teleport="body"><div x-show="konfirmasi" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" @keydown.escape.window="konfirmasi = false"><div @click.outside="konfirmasi = false" class="w-full max-w-md rounded-2xl bg-white p-6 text-left shadow-xl"><h3 class="text-lg font-bold text-slate-900">Hapus tahun ajaran?</h3><p class="mt-2 text-sm text-slate-500">Data {{ $schoolYear->nama }} akan dihapus.</p><div class="mt-6 flex justify-end gap-3"><button type="button" @click="konfirmasi = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600">Batal</button><form method="POST" action="{{ route('school-years.destroy', $schoolYear) }}">@csrf @method('DELETE')<button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white">Hapus</button></form></div></div></div></template></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada tahun ajaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$schoolYears" />
    </div>
</x-app-layout>
