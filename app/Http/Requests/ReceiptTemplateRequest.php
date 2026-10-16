<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_sekolah', 'bendahara']) === true;
    }

    public function rules(): array
    {
        return ['nama_template' => ['required', 'string', 'max:100'], 'header_text' => ['nullable', 'string', 'max:5000'], 'footer_text' => ['nullable', 'string', 'max:5000'], 'show_logo' => ['nullable', 'boolean'], 'show_qr' => ['nullable', 'boolean'], 'ukuran' => ['required', 'in:a4,thermal58,thermal80'], 'aktif' => ['nullable', 'boolean']];
    }

    public function attributes(): array
    {
        return ['nama_template' => 'nama template', 'header_text' => 'teks header', 'footer_text' => 'teks footer', 'ukuran' => 'ukuran struk'];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'in' => ':attribute tidak valid.', 'max.string' => ':attribute maksimal :max karakter.'];
    }
}
