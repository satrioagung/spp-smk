<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiptTemplateRequest;
use App\Models\ReceiptTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $institutionId = $this->activeInstitutionId($request);
        return view('receipt-templates.index', ['templates' => ReceiptTemplate::where('institution_id', $institutionId)->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('receipt-templates.create');
    }

    public function store(ReceiptTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['institution_id'] = $this->activeInstitutionId($request);
        $data['show_logo'] = $request->boolean('show_logo');
        $data['show_qr'] = $request->boolean('show_qr');
        $data['aktif'] = $request->boolean('aktif');
        ReceiptTemplate::create($data);
        return to_route('receipt-templates.index')->with('success', 'Template struk berhasil ditambahkan.');
    }

    public function show(Request $request, ReceiptTemplate $receiptTemplate): View
    {
        $this->ensureTenant($request, $receiptTemplate);
        return view('receipt-templates.show', ['template' => $receiptTemplate]);
    }

    public function edit(Request $request, ReceiptTemplate $receiptTemplate): View
    {
        $this->ensureTenant($request, $receiptTemplate);
        return view('receipt-templates.edit', ['template' => $receiptTemplate]);
    }

    public function update(ReceiptTemplateRequest $request, ReceiptTemplate $receiptTemplate): RedirectResponse
    {
        $this->ensureTenant($request, $receiptTemplate);
        $data = $request->validated();
        $data['show_logo'] = $request->boolean('show_logo');
        $data['show_qr'] = $request->boolean('show_qr');
        $data['aktif'] = $request->boolean('aktif');
        $receiptTemplate->update($data);
        return to_route('receipt-templates.index')->with('success', 'Template struk berhasil diperbarui.');
    }

    public function destroy(Request $request, ReceiptTemplate $receiptTemplate): RedirectResponse
    {
        $this->ensureTenant($request, $receiptTemplate);
        $receiptTemplate->delete();
        return to_route('receipt-templates.index')->with('success', 'Template struk berhasil dihapus.');
    }

    public function preview(Request $request, ReceiptTemplate $template): View
    {
        $this->ensureTenant($request, $template);
        $institution = $request->user()->institution ?: \App\Models\Institution::find($template->institution_id);
        $dummy = ['school_name' => $institution?->nama ?: 'Nama Lembaga', 'school_address' => $institution?->alamat ?: 'Alamat lembaga', 'student_name' => 'Siswa Contoh', 'student_nis' => '20250001', 'student_class' => 'X TKJ 1', 'payment_type' => 'SPP Bulanan', 'payment_period' => 'Januari 2025', 'amount' => 'Rp 200.000', 'payment_date' => now()->translatedFormat('d F Y'), 'nomor_transaksi' => 'SMK/TRX/'.now()->year.'/00001', 'petugas' => $request->user()->name];
        $renderedHeader = nl2br(e(strtr((string) $template->header_text, collect($dummy)->mapWithKeys(fn ($value, $key) => ['{{ '.$key.' }}' => $value])->all())));
        $renderedFooter = nl2br(e(strtr((string) $template->footer_text, collect($dummy)->mapWithKeys(fn ($value, $key) => ['{{ '.$key.' }}' => $value])->all())));
        return view('receipt-templates.preview', compact('template', 'renderedHeader', 'renderedFooter', 'dummy'));
    }

    private function ensureTenant(Request $request, ReceiptTemplate $template): void
    {
        abort_unless((int) $template->institution_id === $this->activeInstitutionId($request), 403, 'Anda tidak memiliki akses ke template ini.');
    }
}
