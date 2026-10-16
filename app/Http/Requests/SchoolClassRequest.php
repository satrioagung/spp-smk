<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_sekolah', 'bendahara']) === true;
    }

    public function rules(): array
    {
        return [
            'school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'nama_kelas' => ['required', 'string', 'max:100'],
            'tingkat' => ['required', 'in:X,XI,XII'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['school_year_id' => 'tahun ajaran', 'nama_kelas' => 'nama kelas', 'tingkat' => 'tingkat', 'aktif' => 'status aktif'];
    }

    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'exists' => ':attribute tidak ditemukan.', 'in' => ':attribute tidak valid.', 'max' => ':attribute maksimal :max karakter.'];
    }
}
