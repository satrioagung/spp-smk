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
use Illuminate\Support\Facades\Storage;
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

    public function cetakStruk(Request $request, Payment $payment): mixed
    {
        $this->ensureTenant($request, $payment);
        $template = ReceiptTemplate::active()->where('institution_id', $payment->institution_id)->latest()->first();
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', 'struk-'.$payment->nomor_transaksi) ?: 'struk-pembayaran';
        return $this->receiptService->generate($payment, $template)->download($filename.'.pdf');
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
