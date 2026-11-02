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

    public function view(User $user, User $model, ?string $type = null): bool
    {
        return ($user->is($model) || $this->hasAnyPermission($user, 'view-any', 'view'))
            && $this->hasTypedPermission($user, 'view', $type);
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
            // Only bypass-authorization, which passes in Gate::before, lets a user change their own roles and permissions.
            'roles', 'permissions' => ! $user->is($model) && $this->hasTypedPermission($user, 'update', $type),
            default => $this->hasTypedPermission($user, 'update', $type),
        };
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->is($model) || ! $this->hasPermission($user, 'delete')) {
            return false;
        }

        return ! $model->checkPermissionTo(config()->string('system.permissions.bypass-authorization'))
            || $user->checkPermissionTo(config()->string('system.permissions.bypass-authorization'));
    }
}
