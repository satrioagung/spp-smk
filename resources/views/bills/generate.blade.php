<x-app-layout>
    <x-slot name="header"><div><h2 class="text-xl font-bold text-slate-900">Generate Tagihan</h2><p class="mt-1 text-sm text-slate-500">Pilih parameter, lihat preview, lalu konfirmasi pembuatan tagihan.</p></div></x-slot>
    @if(!$schoolYear)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">Belum ada tahun ajaran aktif. Aktifkan tahun ajaran terlebih dahulu.</div>
    @elseif($paymentTypes->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">Belum ada jenis pembayaran aktif untuk tahun ajaran {{ $schoolYear->nama }}. Buat jenis pembayaran terlebih dahulu.</div>
    @else
        <div x-data="{ paymentType: @js((string) old('payment_type_id', $previewParams['payment_type_id'] ?? $paymentTypes->first()->id)), target: @js(old('target', $previewParams['target'] ?? 'all')), nominal: @js((string) old('nominal', $previewParams['nominal'] ?? $paymentTypes->first()->nominal_default)), nominalTouched: false, types: @js($paymentTypes->mapWithKeys(fn ($type) => [$type->id => $type->tipe->value])->all()), nominalMap: @js($paymentTypes->mapWithKeys(fn ($type) => [$type->id => (string) $type->nominal_default])->all()) }" class="space-y-6">
            <form method="POST" action="{{ route('bills.preview') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
                @csrf
                <div class="mb-6 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">1</span><div><h3 class="font-bold text-slate-900">Pilih Parameter</h3><p class="text-sm text-slate-500">Tagihan akan dibuat untuk tahun ajaran {{ $schoolYear->nama }}.</p></div></div>
                <div class="grid gap-6 md:grid-cols-2">
                    <div><label for="payment_type_id" class="block text-sm font-medium text-slate-700">Jenis Pembayaran <span class="text-red-500">*</span></label><select id="payment_type_id" name="payment_type_id" x-model="paymentType" @change="if (!nominalTouched) nominal = nominalMap[paymentType]" required class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500">@foreach($paymentTypes as $type)<option value="{{ $type->id }}">{{ $type->nama }} — Rp {{ number_format((float) $type->nominal_default, 0, ',', '.') }}</option>@endforeach</select><x-input-error :messages="$errors->get('payment_type_id')" class="mt-2" /></div>
                    <div><label for="target" class="block text-sm font-medium text-slate-700">Sasaran <span class="text-red-500">*</span></label><select id="target" name="target" x-model="target" required class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500"><option value="all">Semua Siswa</option><option value="class">Per Kelas</option><option value="student">Per Siswa</option></select><x-input-error :messages="$errors->get('target')" class="mt-2" /></div>
                    <div x-show="target === 'class'"><label for="class_id" class="block text-sm font-medium text-slate-700">Kelas</label><select id="class_id" name="class_id" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500"><option value="">Pilih kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) old('class_id', $previewParams['class_id'] ?? '') === (string) $class->id)>{{ $class->nama_kelas }}</option>@endforeach</select><x-input-error :messages="$errors->get('class_id')" class="mt-2" /></div>
                    <div x-show="target === 'student'"><label for="student_id" class="block text-sm font-medium text-slate-700">Siswa</label><select id="student_id" name="student_id" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500"><option value="">Pilih siswa</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected((string) old('student_id', $previewParams['student_id'] ?? '') === (string) $student->id)>{{ $student->nama_lengkap }} — {{ $student->nis }}</option>@endforeach</select><x-input-error :messages="$errors->get('student_id')" class="mt-2" /></div>
                    <div><label for="tahun" class="block text-sm font-medium text-slate-700">Tahun <span class="text-red-500">*</span></label><input id="tahun" type="number" name="tahun" min="2000" max="2100" value="{{ old('tahun', $previewParams['tahun'] ?? now()->year) }}" required class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500"></div>
                    <div><label for="jatuh_tempo" class="block text-sm font-medium text-slate-700">Jatuh Tempo</label><input id="jatuh_tempo" type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo', $previewParams['jatuh_tempo'] ?? '') }}" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500"></div>
                    <div><label for="nominal" class="block text-sm font-medium text-slate-700">Nominal <span class="text-red-500">*</span></label><input id="nominal" type="number" name="nominal" x-model="nominal" @input="nominalTouched = true" min="0" step="0.01" required class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500"><x-input-error :messages="$errors->get('nominal')" class="mt-2" /></div>
                    <div><label for="keterangan" class="block text-sm font-medium text-slate-700">Keterangan</label><input id="keterangan" name="keterangan" value="{{ old('keterangan', $previewParams['keterangan'] ?? '') }}" maxlength="1000" class="mt-2 block w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500"></div>
                </div>
                <div x-show="types[paymentType] === 'bulanan'" class="mt-6"><p class="block text-sm font-medium text-slate-700">Bulan <span class="text-red-500">*</span></p><div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6">@foreach($months as $number => $month)<label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="bulan[]" value="{{ $number }}" @checked(in_array($number, array_map('intval', (array) old('bulan', $previewParams['bulan'] ?? [])), true)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"><span>{{ $month }}</span></label>@endforeach</div><x-input-error :messages="$errors->get('bulan')" class="mt-2" /></div>
                <div class="mt-8 flex justify-end"><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Lihat Preview</button></div>
            </form>
            @if($preview)
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 sm:p-8">
                    <div class="mb-6 flex items-center gap-3"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">2</span><div><h3 class="font-bold text-slate-900">Preview Tagihan</h3><p class="text-sm text-slate-500">{{ $preview['willCreate'] }} tagihan baru akan dibuat, {{ $preview['skipped'] }} dilewati karena sudah ada.</p></div></div>
                    <div class="mb-4 flex flex-wrap gap-3 text-sm"><span class="rounded-full bg-blue-50 px-3 py-1.5 text-blue-700">Akan dibuat: <strong>{{ $preview['willCreate'] }}</strong></span><span class="rounded-full bg-slate-100 px-3 py-1.5 text-slate-600">Sudah ada: <strong>{{ $preview['skipped'] }}</strong></span></div>
                    <div class="max-h-96 overflow-auto rounded-xl border border-slate-100"><table class="min-w-full divide-y divide-slate-100 text-sm"><thead class="sticky top-0 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">No</th><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Kelas</th><th class="px-4 py-3">NIS</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Nominal</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">
                        @forelse($preview['rows'] as $row)<tr><td class="px-4 py-3">{{ $loop->iteration }}</td><td class="px-4 py-3 font-medium text-slate-800">{{ $row['student']->nama_lengkap }}</td><td class="px-4 py-3">{{ $row['student']->schoolClass?->nama_kelas }}</td><td class="px-4 py-3">{{ $row['student']->nis }}</td><td class="px-4 py-3">{{ $row['month'] ? $months[$row['month']] : 'Tahunan/sekali' }} {{ $row['year'] }}</td><td class="px-4 py-3">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td><td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $row['exists'] ? 'bg-slate-100 text-slate-600' : 'bg-blue-100 text-blue-700' }}">{{ $row['exists'] ? 'Sudah Ada' : 'Baru' }}</span></td></tr>@empty<tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada siswa yang dapat ditagih.</td></tr>@endforelse
                    </tbody></table></div>
                    <form method="POST" action="{{ route('bills.generate') }}" class="mt-6 flex justify-end gap-3">
                        @csrf
                        @foreach($previewParams as $key => $value)
                            @if(is_array($value))
                                @foreach($value as $item)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <input type="hidden" name="confirm" value="1">
                        <a href="{{ route('bills.generate.form') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-slate-600">Kembali</a>
                        <button class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Konfirmasi Generate</button>
                    </form>
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
