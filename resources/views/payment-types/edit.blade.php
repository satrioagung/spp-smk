<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Edit Jenis Pembayaran</h2><p class="mt-1 text-sm text-slate-500">Perbarui {{ $paymentType->nama }}.</p></div></x-slot>
    <div class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8"><x-payment-type-form :payment-type="$paymentType" :school-years="$schoolYears" :form-action="route('payment-types.update', $paymentType)" submit-label="Simpan Perubahan" :is-edit="true" /></div>
</x-app-layout>
