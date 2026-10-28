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
        getAllPermissions as protected getOwnPermissions;
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
     * @return Collection<int, Permission>
     */
    public function getAllPermissions(): Collection
    {
        /** @var Collection<int, Permission> $permissions */
        $permissions = $this->getOwnPermissions()->merge($this->profile?->getAllPermissions() ?? []);

        return $permissions->unique(fn (Permission $permission): mixed => $permission->getKey())->sort()->values();
    }

    /**
     * @param  Builder<static>  $query
     * @param  string|int|array<array-key, mixed>|Permission|Collection<array-key, mixed>|BackedEnum  $permissions
     * @return Builder<static>
     */
    protected function scopePermission(Builder $query, $permissions, bool $without = false): Builder
    {
        $viaProfile = fn (Builder $profile): Builder => $this->profile()->getRelated()->scopePermission($profile, $permissions);

        return $without
            ? $this->scopeOwnPermission($query, $permissions, true)->whereDoesntHave('profile', $viaProfile)
            : $query->where(fn (Builder $query): Builder => $this->scopeOwnPermission($query, $permissions)->orWhereHas('profile', $viaProfile));
    }
}
