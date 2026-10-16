<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div><h2 class="text-xl font-bold text-slate-900">Data Kelas</h2><p class="mt-1 text-sm text-slate-500">Kelola kelas berdasarkan tahun ajaran lembaga aktif.</p></div>
            <a href="{{ route('classes.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">+ Tambah Kelas</a>
        </div>
    </x-slot>

    <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-center"><label for="school_year_id" class="text-sm font-medium text-slate-700">Tahun Ajaran</label><select id="school_year_id" name="school_year_id" onchange="this.form.submit()" class="rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">@foreach($schoolYears as $schoolYear)<option value="{{ $schoolYear->id }}" @selected($yearId === $schoolYear->id)>{{ $schoolYear->nama }}{{ $schoolYear->aktif ? ' (Aktif)' : '' }}</option>@endforeach</select></form>
    </div>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-6 py-4">Nama Kelas</th><th class="px-6 py-4">Tingkat</th><th class="px-6 py-4">Tahun Ajaran</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($classes as $class)
            <tr x-data="{ konfirmasi: false }" class="hover:bg-slate-50/70"><td class="px-6 py-4 font-semibold text-slate-900">{{ $class->nama_kelas }}</td><td class="px-6 py-4 text-slate-600">{{ $class->tingkat }}</td><td class="px-6 py-4 text-slate-600">{{ $class->schoolYear?->nama }}</td><td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $class->aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $class->aktif ? 'Aktif' : 'Nonaktif' }}</span></td><td class="px-6 py-4 text-right"><div class="flex justify-end gap-2"><a href="{{ route('classes.edit', $class) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-100">Edit</a><button type="button" @click="konfirmasi = true" class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-100">Hapus</button></div><template x-teleport="body"><div x-show="konfirmasi" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"><div @click.outside="konfirmasi = false" class="w-full max-w-md rounded-2xl bg-white p-6 text-left shadow-xl"><h3 class="text-lg font-bold text-slate-900">Hapus kelas?</h3><p class="mt-2 text-sm text-slate-500">{{ $class->nama_kelas }} hanya dapat dihapus jika belum memiliki siswa.</p><div class="mt-6 flex justify-end gap-3"><button type="button" @click="konfirmasi = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600">Batal</button><form method="POST" action="{{ route('classes.destroy', $class) }}">@csrf @method('DELETE')<button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white">Hapus</button></form></div></div></div></template></td></tr>
        @empty
            <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada kelas pada tahun ajaran ini.</td></tr>
        @endforelse
    </tbody></table></div><x-pagination :paginator="$classes" /></div>
</x-app-layout>
