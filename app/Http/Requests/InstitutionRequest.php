<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('prefix_nomor_struk')) {
            $this->merge(['prefix_nomor_struk' => strtoupper((string) $this->input('prefix_nomor_struk'))]);
        }
    }

    public function rules(): array
    {
        $institution = $this->route('institution');
        $account = $institution
            ? User::where('institution_id', $institution->id)->get()->first(fn (User $user) => $user->hasRole('admin_sekolah'))
            : null;

        return [
            'nama' => ['required', 'string', 'max:100'],
            'jenjang' => ['required', 'in:TK,SD,SMP,SMA,SMK'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'warna_tema' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_struk' => ['nullable', 'string', 'max:500'],
            'prefix_nomor_struk' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9_-]+$/'],
            'akun_nama' => ['required', 'string', 'max:255'],
            'akun_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($account?->id)],
            'akun_password' => array_values(array_filter([$account ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'])),
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama lembaga',
            'jenjang' => 'jenjang',
            'logo' => 'logo',
            'alamat' => 'alamat',
            'telepon' => 'telepon',
            'email' => 'email',
            'warna_tema' => 'warna tema',
            'footer_struk' => 'footer struk',
            'prefix_nomor_struk' => 'prefix nomor struk',
            'akun_nama' => 'nama pengelola akun',
            'akun_email' => 'email akun lembaga',
            'akun_password' => 'password akun lembaga',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max.string' => ':attribute maksimal :max karakter.',
            'max.file' => ':attribute maksimal berukuran 2 MB.',
            'in' => ':attribute tidak valid.',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'mimes' => ':attribute harus berupa JPG, JPEG, atau PNG.',
            'regex' => ':attribute memiliki format yang tidak valid.',
        ];
    }
}
