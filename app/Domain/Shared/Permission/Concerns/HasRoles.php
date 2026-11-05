<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles as SpatieHasRoles;

trait HasRoles
{
    use HasPermissions, SpatieHasRoles {
        HasPermissions::hasPermissionTo insteadof SpatieHasRoles;
        HasPermissions::hasDirectPermission insteadof SpatieHasRoles;
        HasPermissions::getAllPermissions insteadof SpatieHasRoles;
        HasPermissions::getDirectPermissions insteadof SpatieHasRoles;
        HasPermissions::getPermissionsViaRoles insteadof SpatieHasRoles;
        HasPermissions::getPermissionNames insteadof SpatieHasRoles;
        HasPermissions::givePermissionTo insteadof SpatieHasRoles;
        HasPermissions::syncPermissions insteadof SpatieHasRoles;
        HasPermissions::revokePermissionTo insteadof SpatieHasRoles;
        HasPermissions::scopePermission insteadof SpatieHasRoles;
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        return $this->profile?->hasRole($roles, $guard) ?? false;
    }

    /**
     * @param  string|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     */
    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        $roles = $roles instanceof BackedEnum ? $roles->value : $roles;
        $roles = is_string($roles) ? explode('|', $roles) : $roles;
        /** @var array<array-key, string|int|Role|BackedEnum> $list */
        $list = $roles instanceof Collection ? $roles->all() : Arr::wrap($roles);

        return collect($list)->every(fn (string|int|Role|BackedEnum $role): bool => $this->hasRole($role, $guard));
    }

    /**
     * @param  string|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     */
    public function hasExactRoles($roles, ?string $guard = null): bool
    {
        return $this->profile?->hasExactRoles($roles, $guard) ?? false;
    }

    /**
     * @return Collection<int, string>
     */
    public function getRoleNames(): Collection
    {
        return $this->profile?->getRoleNames() ?? collect();
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function assignRole(...$roles): never
    {
        throw $this->profileOnlyException();
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function syncRoles(...$roles): never
    {
        throw $this->profileOnlyException();
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$role
     */
    public function removeRole(...$role): never
    {
        throw $this->profileOnlyException();
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
            return $query->whereDoesntHave('profile', $viaProfile);
        }

        return $query->whereHas('profile', $viaProfile);
    }
}
