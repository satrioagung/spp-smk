<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentTypeRequest;
use App\Models\PaymentType;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentTypeController extends Controller
{
    public function index(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $schoolYears = SchoolYear::where('institution_id', $institutionId)->orderByDesc('tanggal_mulai')->get();
        $yearId = (int) ($request->input('school_year_id') ?: $schoolYears->firstWhere('aktif', true)?->id);
        $paymentTypes = PaymentType::with('schoolYear')->where('institution_id', $institutionId)->when($yearId, fn ($query) => $query->where('school_year_id', $yearId))->orderBy('nama')->paginate(15)->withQueryString();

        return view('payment-types.index', compact('paymentTypes', 'schoolYears', 'yearId'));
    }

    public function create(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        $schoolYears = SchoolYear::where('institution_id', $institutionId)->orderByDesc('tanggal_mulai')->get();
        return view('payment-types.create', compact('schoolYears'));
    }

    public function store(PaymentTypeRequest $request): RedirectResponse
    {
        $institutionId = $this->activeInstitutionId($request);
        $data = $request->validated();
        $data['institution_id'] = $institutionId;
        $data['kode'] = strtoupper($data['kode']);
        $data['bisa_cicil'] = $request->boolean('bisa_cicil');
        $data['wajib'] = $request->boolean('wajib');
        $data['aktif'] = $request->boolean('aktif');
        PaymentType::create($data);

        return to_route('payment-types.index', ['school_year_id' => $data['school_year_id']])->with('success', 'Jenis pembayaran berhasil ditambahkan.');
    }

    public function edit(Request $request, PaymentType $paymentType): View
    {
        $this->ensureTenant($request, $paymentType);
        $schoolYears = SchoolYear::where('institution_id', $paymentType->institution_id)->orderByDesc('tanggal_mulai')->get();
        return view('payment-types.edit', compact('paymentType', 'schoolYears'));
    }

    public function show(Request $request, PaymentType $paymentType): View
    {
        $this->ensureTenant($request, $paymentType);
        $paymentType->load('schoolYear');
        return view('payment-types.show', compact('paymentType'));
    }

    public function update(PaymentTypeRequest $request, PaymentType $paymentType): RedirectResponse
    {
        $this->ensureTenant($request, $paymentType);
        $data = $request->validated();
        $data['kode'] = strtoupper($data['kode']);
        $data['bisa_cicil'] = $request->boolean('bisa_cicil');
        $data['wajib'] = $request->boolean('wajib');
        $data['aktif'] = $request->boolean('aktif');
        $paymentType->update($data);

        return to_route('payment-types.index', ['school_year_id' => $paymentType->school_year_id])->with('success', 'Jenis pembayaran berhasil diperbarui.');
    }

    public function destroy(Request $request, PaymentType $paymentType): RedirectResponse
    {
        $this->ensureTenant($request, $paymentType);
        if ($paymentType->bills()->withTrashed()->exists()) {
            return back()->withErrors(['payment_type' => 'Jenis pembayaran tidak dapat dihapus karena sudah memiliki tagihan.']);
        }
        $paymentType->delete();
        return to_route('payment-types.index', ['school_year_id' => $paymentType->school_year_id])->with('success', 'Jenis pembayaran berhasil dihapus.');
    }

    private function ensureTenant(Request $request, PaymentType $paymentType): void
    {
        abort_unless((int) $paymentType->institution_id === $this->activeInstitutionId($request), 403, 'Anda tidak memiliki akses ke jenis pembayaran ini.');
    }
}
