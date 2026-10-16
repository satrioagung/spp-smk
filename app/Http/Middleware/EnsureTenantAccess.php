<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('super_admin')) {
            return $next($request);
        }

        $parameters = $request->route()?->parameters() ?? [];
        $resource = collect($parameters)->first(fn ($parameter) => is_object($parameter) && (method_exists($parameter, 'getAttribute') || property_exists($parameter, 'institution_id')));
        $resourceInstitutionId = $request->route('institution_id');

        if ($resourceInstitutionId === null && $resource) {
            $resourceInstitutionId = $resource->institution_id ?? null;
            if ($resourceInstitutionId === null && $resource instanceof \App\Models\Institution) {
                $resourceInstitutionId = $resource->getKey();
            }
        }

        if ($resourceInstitutionId === null) {
            $resourceInstitutionId = collect($parameters)->first(
                fn ($parameter, $name) => str_contains((string) $name, 'institution') && is_scalar($parameter)
            );
        }

        if ($resourceInstitutionId !== null && (int) $resourceInstitutionId !== (int) $user->institution_id) {
            abort(403, 'Anda tidak memiliki akses ke lembaga ini.');
        }

        return $next($request);
    }
}
