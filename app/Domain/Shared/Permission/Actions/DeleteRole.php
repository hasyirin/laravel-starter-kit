<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteRole
{
    use Actionable;

    /**
     * @throws AuthorizationException
     */
    public function execute(Role $role, ?User $actor = null): void
    {
        $actor ??= auth()->user();

        throw_unless($actor instanceof User, AuthorizationException::class);

        $bypassPermission = config()->string('system.permissions.bypass-authorization');

        throw_if($role->checkPermissionTo($bypassPermission) && ! $actor->checkPermissionTo($bypassPermission), AuthorizationException::class);

        $role->delete();
    }
}
