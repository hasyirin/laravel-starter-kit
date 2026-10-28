<?php

declare(strict_types=1);

namespace App\Domain\User\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\User\Models\User;

class UserPolicy extends Policy
{
    protected static string $prefix = 'admin.controls';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view-any');
    }

    public function view(User $user, User $model): bool
    {
        return $user->is($model) || $this->hasAnyPermission($user, 'view-any', 'view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create');
    }

    public function update(User $user, User $model, ?string $type = null): bool
    {
        if (! $this->hasPermission($user, 'update')) {
            return false;
        }

        return match ($type) {
            null => true,
            // Only bypass-authorization, which passes in Gate::before, lets a user change their own roles and permissions.
            'roles', 'permissions' => ! $user->is($model) && $this->hasPermission($user, "update-$type"),
            default => $this->hasPermission($user, "update-$type"),
        };
    }

    public function delete(User $user, User $model): bool
    {
        return $this->hasPermission($user, 'delete');
    }
}
