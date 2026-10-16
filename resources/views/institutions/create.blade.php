<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Tambah Lembaga</h2><p class="mt-1 text-sm text-slate-500">Tambahkan sekolah baru ke SIP-SPP.</p></div></x-slot>
    <div class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">@include('institutions.partials.form', ['formAction' => route('institutions.store'), 'submitLabel' => 'Simpan Lembaga', 'account' => null])</div>
</x-app-layout>
