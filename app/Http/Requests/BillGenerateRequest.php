<?php

namespace App\Http\Requests;

use App\Enums\PaymentTypeKind;
use App\Models\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_sekolah', 'bendahara']) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['target' => $this->input('target', 'all')]);
    }

    public function rules(): array
    {
        $institutionId = (int) $this->session()->get('active_institution_id', $this->user()?->institution_id);
        $paymentType = PaymentType::where('institution_id', $institutionId)->find($this->integer('payment_type_id'));

        return [
            'payment_type_id' => ['required', 'integer', Rule::exists('payment_types', 'id')->where(fn ($query) => $query->where('institution_id', $institutionId)->where('aktif', true))],
            'target' => ['required', 'in:all,class,student'],
            'class_id' => ['nullable', 'required_if:target,class', 'integer', Rule::exists('classes', 'id')->where(fn ($query) => $query->where('institution_id', $institutionId))],
            'student_id' => ['nullable', 'required_if:target,student', 'integer', Rule::exists('students', 'id')->where(fn ($query) => $query->where('institution_id', $institutionId)->where('aktif', true))],
            'bulan' => [$paymentType?->tipe === PaymentTypeKind::BULANAN ? 'required' : 'nullable', 'array'],
            'bulan.*' => ['integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'jatuh_tempo' => ['nullable', 'date'],
            'nominal' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['payment_type_id' => 'jenis pembayaran', 'target' => 'sasaran', 'class_id' => 'kelas', 'student_id' => 'siswa', 'bulan' => 'bulan', 'tahun' => 'tahun', 'jatuh_tempo' => 'jatuh tempo', 'nominal' => 'nominal'];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'required_if' => ':attribute wajib diisi sesuai sasaran.', 'exists' => ':attribute tidak ditemukan pada lembaga aktif.', 'array' => ':attribute tidak valid.', 'between' => ':attribute tidak valid.'];
    }
}
