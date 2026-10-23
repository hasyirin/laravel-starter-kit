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
        assignRole as private attachRoles;
        givePermissionTo as private attachPermissions;
    }

    /**
     * @var list<array<array-key, mixed>>
     */
    private array $rolesToAttach = [];

    /**
     * @var list<array<array-key, mixed>>
     */
    private array $permissionsToAttach = [];

    /**
     * @param  array{touch?: bool|null}  $options
     */
    public function save(array $options = []): bool
    {
        if ($this->rolesToAttach === [] && $this->permissionsToAttach === []) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(function () use ($options): bool {
            if (! parent::save($options)) {
                return false;
            }

            $this->attachRoles($this->rolesToAttach);
            $this->attachPermissions($this->permissionsToAttach);
            $this->rolesToAttach = [];
            $this->permissionsToAttach = [];

            return true;
        });
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function assignRole(...$roles): static
    {
        throw_if($this->exists, $this->frozenRolesException());

        $this->rolesToAttach[] = $roles;

        return $this;
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function syncRoles(...$roles): static
    {
        throw_if($this->exists, $this->frozenRolesException());

        $this->rolesToAttach = [$roles];

        return $this;
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

        $this->permissionsToAttach[] = $permissions;

        return $this;
    }

    /**
     * @param  string|int|array<array-key, mixed>|Permission|Collection<array-key, mixed>|BackedEnum  ...$permissions
     */
    public function syncPermissions(...$permissions): static
    {
        throw_if($this->exists, $this->frozenRolesException());

        $this->permissionsToAttach = [$permissions];

        return $this;
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
