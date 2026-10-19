<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\User;

class RolePolicy extends Policy
{
    protected static string $prefix = 'admin.controls';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view-any');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->hasAnyPermission($user, 'view-any', 'view') || $user->hasRole($role);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'create');
    }

    public function update(User $user, Role $role, ?string $type = null): bool
    {
        if (! $this->hasPermission($user, 'update')) {
            return false;
        }

        return match ($type) {
            null => true,
            'name' => ! $role->is_system,
            default => $this->hasPermission($user, "update-$type"),
        };
    }

    public function delete(User $user, Role $role): bool
    {
        return ! $role->is_system && $this->hasPermission($user, 'delete');
    }
}
