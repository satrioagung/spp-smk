<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Tambah Kelas</h2><p class="mt-1 text-sm text-slate-500">Tambahkan kelas untuk lembaga aktif.</p></div></x-slot>
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8"><x-class-form :school-years="$schoolYears" :form-action="route('classes.store')" submit-label="Simpan" /></div>
</x-app-layout>
