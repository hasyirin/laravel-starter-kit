<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasPermissions as SpatieHasPermissions;

trait HasPermissions
{
    use SpatieHasPermissions {
        hasPermissionTo as protected hasOwnPermissionTo;
        hasDirectPermission as protected hasOwnDirectPermission;
        getAllPermissions as protected getOwnPermissions;
        getPermissionsViaRoles as protected getOwnPermissionsViaRoles;
        scopePermission as protected scopeOwnPermission;
    }

    /**
     * @param  string|int|Permission|BackedEnum  $permission
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        return $this->hasOwnPermissionTo($permission, $guardName) || ($this->profile?->hasPermissionTo($permission, $guardName) ?? false);
    }

    /**
     * @param  string|int|Permission|BackedEnum  $permission
     */
    public function hasDirectPermission($permission): bool
    {
        return $this->hasOwnDirectPermission($permission) || ($this->profile?->hasDirectPermission($permission) ?? false);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getAllPermissions(): Collection
    {
        return $this->mergePermissions($this->getOwnPermissions(), $this->profile?->getAllPermissions());
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissionsViaRoles(): Collection
    {
        return $this->mergePermissions($this->getOwnPermissionsViaRoles(), $this->profile?->getPermissionsViaRoles());
    }

    /**
     * @return Collection<int, string>
     */
    public function getPermissionNames(): Collection
    {
        return $this->getDirectPermissions()->map(fn (Permission $permission): string => $permission->name);
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
            return $this->scopeOwnPermission($query, $permissions, true)->whereDoesntHave('profile', $viaProfile);
        }

        return $query->where(fn (Builder $query): Builder => $this->scopeOwnPermission($query, $permissions)->orWhereHas('profile', $viaProfile));
    }

    /**
     * @param  Collection<int, Permission>  $own
     * @param  Collection<int, Permission>|null  $profile
     * @return Collection<int, Permission>
     */
    private function mergePermissions(Collection $own, ?Collection $profile): Collection
    {
        return $own->merge($profile ?? [])
            ->unique(fn (Permission $permission): mixed => $permission->getKey())
            ->sort()
            ->values();
    }
}
