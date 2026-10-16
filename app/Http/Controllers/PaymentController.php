<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\ReceiptTemplate;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\ReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService, private readonly ReceiptService $receiptService)
    {
    }

    public function index(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $query = Payment::with(['student.schoolClass', 'bill.paymentType', 'bills.paymentType', 'petugas'])->where('institution_id', $institutionId)
            ->when($request->filled('tanggal_mulai'), fn ($query) => $query->whereDate('tanggal_bayar', '>=', $request->input('tanggal_mulai')))
            ->when($request->filled('tanggal_selesai'), fn ($query) => $query->whereDate('tanggal_bayar', '<=', $request->input('tanggal_selesai')))
            ->when($request->filled('class_id'), fn ($query) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('class_id', $request->integer('class_id'))))
            ->when($request->filled('metode_bayar'), fn ($query) => $query->where('metode_bayar', $request->input('metode_bayar')))
            ->when($request->filled('petugas_id'), fn ($query) => $query->where('petugas_id', $request->integer('petugas_id')))
            ->latest('tanggal_bayar')->latest('id');
        $allPayments = (clone $query)->get(['id', 'nominal_bayar']);

        return view('payments.index', ['payments' => $query->paginate(20)->withQueryString(), 'classes' => SchoolClass::where('institution_id', $institutionId)->orderBy('nama_kelas')->get(), 'petugas' => User::where('institution_id', $institutionId)->role(['super_admin', 'admin_sekolah', 'bendahara'])->orderBy('name')->get(), 'summary' => ['count' => $allPayments->count(), 'total' => $allPayments->sum(fn (Payment $payment) => (float) $payment->nominal_bayar)]]);
    }

    public function create(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $selectedIds = $this->requestedBillIds($request);
        abort_if($selectedIds === [], 422, 'Pilih minimal satu tagihan.');

        $requestedBills = Bill::with(['student.schoolClass', 'paymentType'])
            ->where('institution_id', $institutionId)
            ->whereIn('id', $selectedIds)
            ->get()
            ->sortBy(fn (Bill $bill): int => array_search($bill->id, $selectedIds, true))
            ->values();

        abort_if($requestedBills->count() !== count($selectedIds), 404, 'Sebagian tagihan yang dipilih tidak ditemukan.');
        $selectedBills = $requestedBills->filter(fn (Bill $bill): bool => in_array($bill->status, ['belum_bayar', 'sebagian'], true) && $bill->sisa_tagihan > 0)->values();
        abort_if($selectedBills->isEmpty(), 422, 'Semua tagihan yang dipilih sudah lunas.');
        abort_if($selectedBills->pluck('student_id')->unique()->count() > 1, 422, 'Tagihan harus berasal dari siswa yang sama.');
        $selectedIds = $selectedBills->pluck('id')->values()->all();

        $student = $selectedBills->first()->student;
        $bills = Bill::with(['student.schoolClass', 'paymentType'])
            ->where('institution_id', $institutionId)
            ->where('student_id', $student->id)
            ->whereIn('status', ['belum_bayar', 'sebagian'])
            ->orderBy('payment_type_id')
            ->orderBy('tahun')
            ->orderByRaw('bulan IS NULL')
            ->orderBy('bulan')
            ->get();

        return view('payments.create', ['bills' => $bills, 'selectedBillIds' => $selectedIds]);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('bukti_file')) {
            $data['bukti_file'] = $request->file('bukti_file')->store('bukti-bayar/'.$this->activeInstitutionId($request), 'public');
        }
        $bills = Bill::with(['student.schoolClass', 'paymentType'])
            ->where('institution_id', $this->activeInstitutionId($request))
            ->whereIn('id', $data['bill_ids'])
            ->get()
            ->sortBy(fn (Bill $bill): int => array_search($bill->id, $data['bill_ids'], true))
            ->values();
        $data['petugas_id'] = $request->user()->id;
        $payment = $this->paymentService->bayarBanyak($bills, $data);
        return to_route('payments.show', $payment)->with('success', "Pembayaran berhasil dicatat. Nomor Transaksi: {$payment->nomor_transaksi}");
    }

    public function show(Request $request, Payment $payment): View
    {
        $this->ensureTenant($request, $payment);
        $payment->load(['student.schoolClass', 'bill.paymentType', 'bills.paymentType', 'petugas']);
        return view('payments.show', compact('payment'));
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'admin_sekolah']), 403, 'Hanya administrator yang dapat menghapus pembayaran.');
        $this->ensureTenant($request, $payment);
        if ($payment->bukti_file) {
            Storage::disk('public')->delete($payment->bukti_file);
        }
        $payment->delete();
        return to_route('payments.index')->with('success', 'Pembayaran dihapus dan status tagihan dikembalikan.');
    }

    public function cetakStruk(Request $request, Payment $payment): View
    {
        $this->ensureTenant($request, $payment);
        $template = ReceiptTemplate::active()->where('institution_id', $payment->institution_id)->latest()->first();
        $token = Str::random(64);
        Cache::put('thermer-print:'.$token, ['payment_id' => $payment->id, 'template_id' => $template?->id], now()->addMinutes(5));
        $baseUrl = $request->getSchemeAndHttpHost();
        $androidResponseUrl = $baseUrl.route('payments.thermer.response', ['payment' => $payment, 'token' => $token], false);
        $iosResponseUrl = $baseUrl.route('payments.thermer.response.ios', ['payment' => $payment, 'token' => $token], false);
        $androidThermerUrl = 'my.bluetoothprint.scheme://'.$androidResponseUrl;
        $iosThermerUrl = 'bprint://'.$iosResponseUrl;

        return $this->receiptService->print($payment, $template, $androidThermerUrl, $iosThermerUrl);
    }

    public function thermerResponse(Request $request, Payment $payment): JsonResponse
    {
        return $this->thermerJsonResponse($request, $payment, false);
    }

    public function thermerResponseIos(Request $request, Payment $payment): JsonResponse
    {
        return $this->thermerJsonResponse($request, $payment, true);
    }

    private function thermerJsonResponse(Request $request, Payment $payment, bool $ios): JsonResponse
    {
        $token = (string) $request->query('token');
        $payload = Cache::get('thermer-print:'.$token);
        abort_unless(is_array($payload) && (int) ($payload['payment_id'] ?? 0) === (int) $payment->id, 403, 'Tautan cetak tidak valid atau sudah kedaluwarsa.');
        $template = ! empty($payload['template_id']) ? ReceiptTemplate::find($payload['template_id']) : null;
        $items = $this->receiptService->thermerPayload($payment, $request->getSchemeAndHttpHost(), $template);

        // Thermer mengharuskan root JSON berupa object numerik, bukan array JSON.
        // Setara dengan json_encode($payload, JSON_FORCE_OBJECT).
        // iOS mengurutkan key sebagai teks, jadi 10 terbaca sebelum 2. Key
        // ber-padding menjaga urutan 000, 001, 002, dan seterusnya.
        if ($ios) {
            $items = array_combine(
                array_map(fn (int $index): string => str_pad((string) $index, 4, '0', STR_PAD_LEFT), array_keys($items)),
                array_values($items),
            );

            return response()->json($items);
        }

        return response()->json((object) $items);
    }

    public function cetakStrukPdf(Request $request, Payment $payment): mixed
    {
        $this->ensureTenant($request, $payment);
        $template = ReceiptTemplate::active()->where('institution_id', $payment->institution_id)->latest()->first();
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', 'struk-'.$payment->nomor_transaksi) ?: 'struk-pembayaran';
        return $this->receiptService->generate($payment, $template)->stream($filename.'.pdf');
    }

    private function ensureTenant(Request $request, Payment $payment): void
    {
        abort_unless((int) $payment->institution_id === $this->activeInstitutionId($request), 403, 'Anda tidak memiliki akses ke pembayaran ini.');
    }

    private function requestedBillIds(Request $request): array
    {
        $billIds = $request->input('bill_ids', []);
        if (! is_array($billIds) || $billIds === []) {
            $billIds = $request->filled('bill_id') ? [$request->integer('bill_id')] : [];
        }

        return array_values(array_unique(array_map('intval', $billIds)));
    }
}
