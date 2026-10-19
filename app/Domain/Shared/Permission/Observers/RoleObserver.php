<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Observers;

use App\Domain\Shared\Permission\Models\Role;
use LogicException;

class RoleObserver
{
    public function updating(Role $role): void
    {
        throw_if($role->is_system && $role->isDirty('name'), LogicException::class, 'System roles cannot be renamed.');
    }
}
