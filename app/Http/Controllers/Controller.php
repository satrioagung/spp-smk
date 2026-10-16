<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    protected function activeInstitutionId(Request $request): int
    {
        $institutionId = $request->session()->get('active_institution_id', $request->user()?->institution_id);

        abort_unless($institutionId, 403, 'Belum ada lembaga aktif.');

        return (int) $institutionId;
    }
}
