<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_sekolah', 'bendahara']) === true;
    }

    public function rules(): array
    {
        $institutionId = (int) $this->session()->get('active_institution_id', $this->user()?->institution_id);
        $paymentType = $this->route('payment_type');

        return [
            'school_year_id' => ['required', 'integer', Rule::exists('school_years', 'id')->where(fn ($query) => $query->where('institution_id', $institutionId))],
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('payment_types', 'kode')->where(fn ($query) => $query->where('institution_id', $institutionId)->where('school_year_id', $this->input('school_year_id')))->ignore($paymentType?->id)],
            'nominal_default' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'tipe' => ['required', 'in:bulanan,tahunan,sekali,bebas'],
            'bisa_cicil' => ['nullable', 'boolean'],
            'wajib' => ['nullable', 'boolean'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['school_year_id' => 'tahun ajaran', 'nama' => 'nama jenis pembayaran', 'kode' => 'kode', 'nominal_default' => 'nominal default', 'tipe' => 'tipe'];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'exists' => ':attribute tidak ditemukan pada lembaga aktif.', 'unique' => 'Kode sudah digunakan pada tahun ajaran tersebut.', 'regex' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, atau garis bawah.', 'numeric' => ':attribute harus berupa angka.', 'min' => ':attribute tidak boleh negatif.'];
    }
}
