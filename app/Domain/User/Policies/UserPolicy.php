<?php

declare(strict_types=1);

namespace App\Domain\User\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;

class UserPolicy extends Policy
{
    protected static string $prefix = 'internal.controls';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view-any');
    }

    public function view(User $user, User $model, ?string $type = null): bool
    {
        return ($user->is($model) || $this->hasAnyPermission($user, 'view-any', 'view'))
            && $this->hasTypedPermission($user, 'view', $type);
    }

    public function create(User $user, ?string $type = null): bool
    {
        return $this->hasPermission($user, 'create') && $this->hasTypedPermission($user, 'update', $type);
    }

    public function update(User $user, User $model, ?string $type = null): bool
    {
        if (! $this->hasPermission($user, 'update') || ! $this->reaches($user, $model)) {
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

        return $this->reaches($user, $model);
    }

    public function impersonate(User $user, User $model): bool
    {
        if (Impersonation::forbids($user, $model)) {
            return false;
        }

        return $this->hasAllPermissions($user, 'impersonate', 'update') && $this->reaches($user, $model);
    }

    private function reaches(User $user, User $model): bool
    {
        return ! $model->checkPermissionTo(config()->string('system.permissions.bypass-authorization'))
            || $user->checkPermissionTo(config()->string('system.permissions.bypass-authorization'));
    }
}
