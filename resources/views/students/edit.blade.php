<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Edit Data Siswa</h2><p class="mt-1 text-sm text-slate-500">Perbarui data {{ $student->nama_lengkap }}.</p></div></x-slot>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8"><div class="mb-6 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Tahun ajaran: <strong>{{ $student->schoolYear?->nama }}</strong></div><x-student-form :student="$student" :classes="$classes" :form-action="route('students.update', $student)" submit-label="Simpan Perubahan" :is-edit="true" /></div>
</x-app-layout>
