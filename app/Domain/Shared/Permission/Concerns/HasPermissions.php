<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use LogicException;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasPermissions as SpatieHasPermissions;

trait HasPermissions
{
    use SpatieHasPermissions;

    /**
     * @param  string|int|Permission|BackedEnum  $permission
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        return $this->profile?->hasPermissionTo($permission, $guardName) ?? false;
    }

    /**
     * @param  string|int|Permission|BackedEnum  $permission
     */
    public function hasDirectPermission($permission): bool
    {
        return $this->profile?->hasDirectPermission($permission) ?? false;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getAllPermissions(): Collection
    {
        return $this->profile?->getAllPermissions() ?? collect();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getDirectPermissions(): Collection
    {
        return $this->profile?->getDirectPermissions() ?? collect();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissionsViaRoles(): Collection
    {
        return $this->profile?->getPermissionsViaRoles() ?? collect();
    }

    /**
     * @return Collection<int, string>
     */
    public function getPermissionNames(): Collection
    {
        return $this->profile?->getPermissionNames() ?? collect();
    }

    /**
     * @param  string|int|array<array-key, mixed>|Permission|Collection<array-key, mixed>|BackedEnum  ...$permissions
     */
    public function givePermissionTo(...$permissions): never
    {
        throw $this->profileOnlyException();
    }

    /**
     * @param  string|int|array<array-key, mixed>|Permission|Collection<array-key, mixed>|BackedEnum  ...$permissions
     */
    public function syncPermissions(...$permissions): never
    {
        throw $this->profileOnlyException();
    }

    /**
     * @param  Permission|array<array-key, Permission|string>|string|BackedEnum  $permission
     */
    public function revokePermissionTo($permission): never
    {
        throw $this->profileOnlyException();
    }

    /**
     * @param  Builder<static>  $query
     * @param  string|int|array<array-key, mixed>|Permission|Collection<array-key, mixed>|BackedEnum  $permissions
     * @return Builder<static>
     */
    protected function scopePermission(Builder $query, $permissions, bool $without = false): Builder
    {
        $viaProfile = fn (Builder $profile): Builder => $this->profile()->getRelated()->scopePermission($profile, $permissions);

        if ($without) {
            return $query->whereDoesntHave('profile', $viaProfile);
        }

        return $query->whereHas('profile', $viaProfile);
    }

    private function profileOnlyException(): LogicException
    {
        return new LogicException('Users get roles and permissions only through a profile.');
    }
}
