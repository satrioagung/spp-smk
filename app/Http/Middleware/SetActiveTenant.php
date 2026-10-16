<?php

namespace App\Http\Middleware;

use App\Models\Institution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            if ($user->institution_id !== null) {
                $request->session()->put('active_institution_id', $user->institution_id);
            } elseif ($user->hasRole('super_admin')) {
                $activeInstitutionId = $request->session()->get('active_institution_id');
                if (! $activeInstitutionId || ! Institution::active()->whereKey($activeInstitutionId)->exists()) {
                    $firstInstitution = Institution::active()->orderBy('id')->first();
                    $firstInstitution
                        ? $request->session()->put('active_institution_id', $firstInstitution->getKey())
                        : $request->session()->forget('active_institution_id');
                }
            }
        }

        return $next($request);
    }
}
