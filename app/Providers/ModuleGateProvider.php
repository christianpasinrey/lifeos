<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ModuleGateProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if (str_starts_with($ability, 'module:')) {
                $slug = substr($ability, 7);
                return $user->hasModule($slug);
            }

            if (str_starts_with($ability, 'feature:')) {
                $parts = explode('.', substr($ability, 8), 2);
                if (count($parts) === 2) {
                    return $user->hasModuleFeature($parts[0], $parts[1]);
                }
            }

            return null;
        });
    }
}
