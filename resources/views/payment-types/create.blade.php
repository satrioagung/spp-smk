<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Tambah Jenis Pembayaran</h2><p class="mt-1 text-sm text-slate-500">Buat komponen pembayaran untuk tahun ajaran tertentu.</p></div></x-slot>
    <div class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8"><x-payment-type-form :school-years="$schoolYears" :form-action="route('payment-types.store')" submit-label="Simpan" /></div>
</x-app-layout>
