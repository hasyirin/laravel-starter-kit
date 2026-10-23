<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Concerns;

use BackedEnum;
use Illuminate\Support\Collection;
use LogicException;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;

trait HasFrozenRoles
{
    use HasRoles {
        assignRole as protected assignRoleBeforeSave;
        syncRoles as protected syncRolesBeforeSave;
        givePermissionTo as protected givePermissionToBeforeSave;
        syncPermissions as protected syncPermissionsBeforeSave;
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function assignRole(...$roles): static
    {
        throw_if($this->exists, $this->frozenRolesException());

        return $this->assignRoleBeforeSave(...$roles);
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function syncRoles(...$roles): static
    {
        throw_if($this->exists, $this->frozenRolesException());

        return $this->syncRolesBeforeSave(...$roles);
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$role
     */
    public function removeRole(...$role): never
    {
        throw $this->frozenRolesException();
    }

    /**
     * @param  string|int|array<array-key, mixed>|Permission|Collection<array-key, mixed>|BackedEnum  ...$permissions
     */
    public function givePermissionTo(...$permissions): static
    {
        throw_if($this->exists, $this->frozenRolesException());

        return $this->givePermissionToBeforeSave(...$permissions);
    }

    /**
     * @param  string|int|array<array-key, mixed>|Permission|Collection<array-key, mixed>|BackedEnum  ...$permissions
     */
    public function syncPermissions(...$permissions): static
    {
        throw_if($this->exists, $this->frozenRolesException());

        return $this->syncPermissionsBeforeSave(...$permissions);
    }

    /**
     * @param  Permission|array<array-key, Permission|string>|string|BackedEnum  $permission
     */
    public function revokePermissionTo($permission): never
    {
        throw $this->frozenRolesException();
    }

    private function frozenRolesException(): LogicException
    {
        return new LogicException('Roles and permissions can only be given before the first save.');
    }
}
