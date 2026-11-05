<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles as SpatieHasRoles;

trait HasRoles
{
    use HasPermissions, SpatieHasRoles {
        HasPermissions::hasPermissionTo insteadof SpatieHasRoles;
        HasPermissions::hasDirectPermission insteadof SpatieHasRoles;
        HasPermissions::getAllPermissions insteadof SpatieHasRoles;
        HasPermissions::getPermissionsViaRoles insteadof SpatieHasRoles;
        HasPermissions::getPermissionNames insteadof SpatieHasRoles;
        HasPermissions::scopePermission insteadof SpatieHasRoles;
        SpatieHasRoles::hasRole as protected hasOwnRole;
        SpatieHasRoles::getDirectPermissions as protected getOwnDirectPermissions;
        SpatieHasRoles::getRoleNames as protected getOwnRoleNames;
        SpatieHasRoles::scopeRole as protected scopeOwnRole;
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        return $this->hasOwnRole($roles, $guard) || ($this->profile?->hasRole($roles, $guard) ?? false);
    }

    /**
     * @param  string|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     */
    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        return collect($this->roleList($roles))->every(fn (string|int|Role|BackedEnum $role): bool => $this->hasRole($role, $guard));
    }

    /**
     * @param  string|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     */
    public function hasExactRoles($roles, ?string $guard = null): bool
    {
        $list = $this->roleList($roles);

        return $this->getRoleNames()->count() === count($list) && $this->hasAllRoles($list, $guard);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getDirectPermissions(): Collection
    {
        return $this->mergePermissions($this->getOwnDirectPermissions(), $this->profile?->getDirectPermissions());
    }

    /**
     * @return Collection<int, string>
     */
    public function getRoleNames(): Collection
    {
        /** @var Collection<int, string> $names */
        $names = $this->getOwnRoleNames()->merge($this->profile?->getRoleNames() ?? []);

        return $names->unique()->values();
    }

    /**
     * @param  Builder<static>  $query
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     * @return Builder<static>
     */
    protected function scopeRole(Builder $query, $roles, ?string $guard = null, bool $without = false): Builder
    {
        $viaProfile = fn (Builder $profile): Builder => $this->profile()->getRelated()->scopeRole($profile, $roles, $guard);

        if ($without) {
            return $this->scopeOwnRole($query, $roles, $guard, true)->whereDoesntHave('profile', $viaProfile);
        }

        return $query->where(fn (Builder $query): Builder => $this->scopeOwnRole($query, $roles, $guard)->orWhereHas('profile', $viaProfile));
    }

    /**
     * @param  string|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     * @return array<array-key, string|int|Role|BackedEnum>
     */
    private function roleList($roles): array
    {
        $roles = $roles instanceof BackedEnum ? $roles->value : $roles;
        $roles = is_string($roles) ? explode('|', $roles) : $roles;

        /** @var array<array-key, string|int|Role|BackedEnum> $list */
        $list = $roles instanceof Collection ? $roles->all() : Arr::wrap($roles);

        return $list;
    }
}
