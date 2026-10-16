<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_sekolah', 'bendahara']) === true;
    }

    public function rules(): array
    {
        $institutionId = (int) $this->session()->get('active_institution_id', $this->user()?->institution_id);
        $student = $this->route('student');
        $nisRule = Rule::unique('students', 'nis')->where(fn ($query) => $query->where('institution_id', $institutionId));
        if ($student) {
            $nisRule->ignore($student->id);
        }

        return [
            'nis' => ['required', 'string', 'max:10', $nisRule],
            'nisn' => ['nullable', 'string', 'max:10'],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where(fn ($query) => $query->where('institution_id', $institutionId))],
            'no_hp_ortu' => ['nullable', 'string', 'max:30'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['nis' => 'NIS', 'nisn' => 'NISN', 'nama_lengkap' => 'nama lengkap', 'jenis_kelamin' => 'jenis kelamin', 'class_id' => 'kelas', 'no_hp_ortu' => 'nomor HP orang tua', 'aktif' => 'status aktif'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'in' => ':attribute tidak valid.',
            'exists' => ':attribute tidak ditemukan pada lembaga aktif.',
            'unique' => 'NIS sudah digunakan pada lembaga aktif.',
        ];
    }
}
