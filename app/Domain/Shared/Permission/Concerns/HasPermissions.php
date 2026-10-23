<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Concerns;

use BackedEnum;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasPermissions as SpatieHasPermissions;

trait HasPermissions
{
    use SpatieHasPermissions {
        hasPermissionTo as protected hasOwnPermissionTo;
    }

    /**
     * @param  string|int|Permission|BackedEnum  $permission
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        return $this->hasOwnPermissionTo($permission, $guardName) || ($this->profile?->hasPermissionTo($permission, $guardName) ?? false);
    }
}
