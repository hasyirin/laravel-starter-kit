<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (User $user): bool => $user->checkPermissionTo(config()->string('system.permissions.admin.horizon.view')));
    }
}
