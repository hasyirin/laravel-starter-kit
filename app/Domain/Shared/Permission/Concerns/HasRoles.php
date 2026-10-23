<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Concerns;

use BackedEnum;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles as SpatieHasRoles;

trait HasRoles
{
    use HasPermissions, SpatieHasRoles {
        HasPermissions::hasPermissionTo insteadof SpatieHasRoles;
        SpatieHasRoles::hasRole as protected hasOwnRole;
    }

    /**
     * @param  string|int|array<array-key, mixed>|Role|Collection<array-key, mixed>|BackedEnum  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        return $this->hasOwnRole($roles, $guard) || ($this->profile?->hasRole($roles, $guard) ?? false);
    }
}
