<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Edit Tahun Ajaran</h2><p class="mt-1 text-sm text-slate-500">Perbarui informasi {{ $schoolYear->nama }}.</p></div></x-slot>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8"><x-school-year-form :school-year="$schoolYear" :form-action="route('school-years.update', $schoolYear)" submit-label="Simpan Perubahan" :is-edit="true" /></div>
</x-app-layout>
