<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EloquentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        Relation::enforceMorphMap([
            'branch' => Branch::class,
            'department' => Department::class,
            'organization' => Organization::class,
            'position' => Position::class,
            'role' => Role::class,
            'user' => User::class,
        ]);
    }
}
