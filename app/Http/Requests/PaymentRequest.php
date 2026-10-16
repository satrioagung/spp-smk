<?php

namespace App\Http\Requests;

use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $billIds = $this->input('bill_ids');
        if (! is_array($billIds) || $billIds === []) {
            $billIds = $this->filled('bill_id') ? [$this->input('bill_id')] : [];
        }

        $this->merge(['bill_ids' => array_values(array_unique(array_map('intval', $billIds)))]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_sekolah', 'bendahara']) === true;
    }

    public function rules(): array
    {
        $institutionId = (int) $this->session()->get('active_institution_id', $this->user()?->institution_id);
        return [
            'bill_ids' => ['required', 'array', 'min:1'],
            'bill_ids.*' => ['integer', Rule::exists('bills', 'id')->where(fn ($query) => $query->where('institution_id', $institutionId)->whereIn('status', ['belum_bayar', 'sebagian']))],
            'tanggal_bayar' => ['required', 'date'],
            'metode_bayar' => ['required', 'in:tunai,transfer,qris,lainnya'],
            'nominal_bayar' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'bukti_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $institutionId = (int) $this->session()->get('active_institution_id', $this->user()?->institution_id);
            $bills = Bill::where('institution_id', $institutionId)->whereIn('id', $this->input('bill_ids', []))->get();
            if ($bills->isEmpty()) {
                return;
            }

            if ($bills->pluck('student_id')->unique()->count() > 1) {
                $validator->errors()->add('bill_ids', 'Tagihan yang dipilih harus berasal dari siswa yang sama.');
            }

            if ((float) $this->input('nominal_bayar', 0) > $bills->sum(fn (Bill $bill): float => $bill->sisa_tagihan)) {
                $validator->errors()->add('nominal_bayar', 'Nominal pembayaran tidak boleh melebihi total sisa tagihan yang dipilih.');
            }
        });
    }

    public function attributes(): array
    {
        return ['bill_ids' => 'tagihan', 'tanggal_bayar' => 'tanggal pembayaran', 'metode_bayar' => 'metode pembayaran', 'nominal_bayar' => 'nominal bayar', 'bukti_file' => 'bukti pembayaran'];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'exists' => ':attribute tidak ditemukan atau sudah lunas.', 'in' => ':attribute tidak valid.', 'min' => ':attribute minimal Rp 1.', 'mimes' => 'Bukti pembayaran harus JPG, PNG, atau PDF.', 'max.file' => 'Ukuran bukti pembayaran maksimal 2 MB.'];
    }
}
