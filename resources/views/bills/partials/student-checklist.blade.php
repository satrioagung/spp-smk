<form method="GET" action="{{ route('payments.create') }}">
    <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
        <div>
            <p class="font-semibold text-slate-900">Pilih tagihan {{ $bill->student?->nama_lengkap }}</p>
            <p class="text-xs text-slate-500">Centang bulan atau jenis pembayaran yang ingin dibayar.</p>
        </div>
        <span class="text-sm text-slate-600"><span x-text="selected.length"></span> dipilih</span>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach($bill->studentBills as $studentBill)
            @php($canPay = in_array($studentBill->status, ['belum_bayar', 'sebagian'], true) && $studentBill->sisa_tagihan > 0)
            @php($billStatusClass = ['belum_bayar' => 'bg-red-100 text-red-700', 'sebagian' => 'bg-yellow-100 text-yellow-700', 'lunas' => 'bg-emerald-100 text-emerald-700'][$studentBill->status] ?? 'bg-slate-100 text-slate-600')
            <label class="flex min-w-0 items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 {{ $canPay ? 'cursor-pointer hover:border-blue-300' : 'opacity-60' }}">
                <input type="checkbox" name="bill_ids[]" value="{{ $studentBill->id }}" x-model="selected" @disabled(!$canPay) class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-2"><span class="truncate font-semibold text-slate-800">{{ $studentBill->paymentType?->nama }}</span><span class="shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold {{ $billStatusClass }}">{{ $studentBill->label_status }}</span></span><span class="mt-1 block text-xs text-slate-500">{{ $studentBill->bulan ? \Carbon\Carbon::create()->month($studentBill->bulan)->translatedFormat('F').' ' : '' }}{{ $studentBill->tahun }}</span><span class="mt-2 block text-sm font-semibold text-slate-700">Sisa: Rp {{ number_format($studentBill->sisa_tagihan, 0, ',', '.') }}</span></span>
            </label>
        @endforeach
    </div>

    <div class="mt-4 flex flex-wrap justify-end gap-2"><button type="submit" :disabled="selected.length === 0" :class="selected.length === 0 ? 'cursor-not-allowed bg-slate-300' : 'bg-emerald-600 hover:bg-emerald-700'" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white">Bayar yang Dipilih</button><button type="button" @click="buka = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600">Tutup</button></div>
</form>
