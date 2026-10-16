<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchoolYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_sekolah', 'bendahara']) === true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:20'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['nama' => 'nama tahun ajaran', 'tanggal_mulai' => 'tanggal mulai', 'tanggal_selesai' => 'tanggal selesai', 'aktif' => 'status aktif'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'boolean' => ':attribute tidak valid.',
        ];
    }
}
