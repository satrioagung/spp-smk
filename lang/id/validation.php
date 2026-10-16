<?php

return [
    'required' => ':attribute wajib diisi.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'min' => [
        'string' => ':attribute minimal harus terdiri dari :min karakter.',
    ],
    'unique' => ':attribute sudah digunakan.',
    'attributes' => [
        'name' => 'nama',
        'email' => 'alamat email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
    ],
];
