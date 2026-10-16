@php
    $selectedIds = collect((array) old('bill_ids', $selectedBillIds))->map(fn ($id) => (int) $id)->values()->all();
    $billOptions = $bills->map(fn ($bill) => [
        'id' => $bill->id,
        'remaining' => $bill->sisa_tagihan,
    ])->values()->all();
    $initialAmount = old('nominal_bayar', $bills->whereIn('id', $selectedIds)->sum(fn ($bill) => $bill->sisa_tagihan));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Input Pembayaran</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih satu atau beberapa tagihan untuk dicatat dalam satu transaksi.</p>
        </div>
    </x-slot>

    <div x-data="{
        bills: @js($billOptions),
        selected: @js($selectedIds),
        amount: @js((float) $initialAmount),
        isSelected(id) { return this.selected.map(Number).includes(Number(id)); },
        get selectedTotal() { return this.bills.filter((bill) => this.isSelected(bill.id)).reduce((total, bill) => total + Number(bill.remaining), 0); }
    }" class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Pilih Tagihan</h3>
                    <p class="mt-1 text-sm text-slate-500">SPP dapat dipilih beberapa bulan sekaligus. Anda juga dapat menggabungkannya dengan tagihan UAS atau jenis lainnya.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"><span x-text="selected.length"></span> tagihan dipilih</span>
            </div>

            <div class="mt-5 space-y-5">
                @foreach ($bills->groupBy('payment_type_id') as $typeBills)
                    <div>
                        <h4 class="mb-2 text-sm font-bold text-slate-800">{{ $typeBills->first()->paymentType?->nama }}</h4>
                        <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                            @foreach ($typeBills as $bill)
                                <label class="flex cursor-pointer items-center justify-between gap-4 p-4 hover:bg-slate-50">
                                    <span class="flex items-center gap-3">
                                        <input type="checkbox" name="bill_ids[]" value="{{ $bill->id }}" x-model="selected" @checked(in_array($bill->id, $selectedIds, true)) form="payment-form" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        <span>
                                            <span class="block font-medium text-slate-800">{{ $bill->bulan ? \Carbon\Carbon::create()->month($bill->bulan)->translatedFormat('F').' ' : '' }}{{ $bill->tahun }}</span>
                                            <span class="block text-xs text-slate-500">{{ $bill->paymentType?->nama }} · {{ $bill->status === 'sebagian' ? 'Sebagian terbayar' : 'Belum bayar' }}</span>
                                        </span>
                                    </span>
                                    <span class="whitespace-nowrap text-sm font-semibold text-red-700">Rp {{ number_format($bill->sisa_tagihan, 0, ',', '.') }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('bill_ids')" class="mt-3" />
        </div>

        <form id="payment-form" method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
            @csrf
            <div class="mb-6 rounded-xl bg-blue-50 p-4 text-sm text-blue-800">
                Total sisa tagihan yang dipilih: <strong>Rp <span x-text="new Intl.NumberFormat('id-ID').format(selectedTotal)"></span></strong>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="tanggal_bayar" class="block text-sm font-medium text-slate-700">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                    <input id="tanggal_bayar" type="date" name="tanggal_bayar" value="{{ old('tanggal_bayar', now()->format('Y-m-d')) }}" required class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    <x-input-error :messages="$errors->get('tanggal_bayar')" class="mt-2" />
                </div>
                <div>
                    <label for="metode_bayar" class="block text-sm font-medium text-slate-700">Metode Pembayaran <span class="text-red-500">*</span></label>
                    <select id="metode_bayar" name="metode_bayar" required class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="tunai" @selected(old('metode_bayar', 'tunai') === 'tunai')>Tunai</option>
                        <option value="transfer" @selected(old('metode_bayar') === 'transfer')>Transfer Bank</option>
                        <option value="qris" @selected(old('metode_bayar') === 'qris')>QRIS</option>
                        <option value="lainnya" @selected(old('metode_bayar') === 'lainnya')>Lainnya</option>
                    </select>
                    <x-input-error :messages="$errors->get('metode_bayar')" class="mt-2" />
                </div>
                <div>
                    <label for="nominal_bayar" class="block text-sm font-medium text-slate-700">Nominal Bayar <span class="text-red-500">*</span></label>
                    <input id="nominal_bayar" type="number" name="nominal_bayar" x-model.number="amount" min="1" :max="selectedTotal" step="0.01" required class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-2 text-sm text-slate-500">Sisa setelah pembayaran: <strong>Rp <span x-text="new Intl.NumberFormat('id-ID').format(Math.max(selectedTotal - (Number(amount) || 0), 0))"></span></strong></p>
                    <p x-show="amount > 0 && amount < selectedTotal" x-cloak class="mt-2 rounded-lg bg-yellow-50 px-3 py-2 text-sm text-yellow-700">Pembayaran cicilan gabungan.</p>
                    <p x-show="amount > 0 && amount === selectedTotal" x-cloak class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">Semua tagihan yang dipilih akan lunas.</p>
                    <p x-show="amount > selectedTotal" x-cloak class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">Nominal melebihi total tagihan yang dipilih.</p>
                    <x-input-error :messages="$errors->get('nominal_bayar')" class="mt-2" />
                </div>
                <div>
                    <label for="bukti_file" class="block text-sm font-medium text-slate-700">Upload Bukti</label>
                    <input id="bukti_file" type="file" name="bukti_file" accept=".jpg,.jpeg,.png,.pdf" class="mt-2 block w-full rounded-xl border border-slate-200 p-2 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700">
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG, atau PDF. Maksimal 2 MB.</p>
                    <x-input-error :messages="$errors->get('bukti_file')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <label for="keterangan" class="block text-sm font-medium text-slate-700">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="3" maxlength="1000" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">{{ old('keterangan') }}</textarea>
                    <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                </div>
            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('bills.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-slate-600">Batalkan</a>
                <button type="submit" :disabled="selected.length === 0 || amount <= 0 || amount > selectedTotal" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</x-app-layout>
