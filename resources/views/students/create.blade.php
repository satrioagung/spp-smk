<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Tambah Siswa</h2><p class="mt-1 text-sm text-slate-500">Siswa baru akan dicatat pada tahun ajaran aktif.</p></div></x-slot>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">@if($schoolYear)<div class="mb-6 rounded-xl bg-blue-50 px-4 py-3 text-sm text-blue-800">Tahun ajaran aktif: <strong>{{ $schoolYear->nama }}</strong></div><x-student-form :classes="$classes" :form-action="route('students.store')" submit-label="Simpan" />@else<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Belum ada tahun ajaran aktif. Tambahkan dan aktifkan tahun ajaran terlebih dahulu.</div>@endif</div>
</x-app-layout>
