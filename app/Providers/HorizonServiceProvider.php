<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Horizon is local-only in M0. Auth gating arrives with platform admin in M1.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (?Authenticatable $user = null): bool {
            return $user !== null;
        });
    }
}
