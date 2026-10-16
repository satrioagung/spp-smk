<?php

namespace App\Providers;

use App\Models\Institution;
use App\Policies\InstitutionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Institution::class => InstitutionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
