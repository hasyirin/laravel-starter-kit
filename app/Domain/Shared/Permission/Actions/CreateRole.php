<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Support\RoleAuditLog;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Enums\ActivityEvent;

class CreateRole
{
    use Actionable;

    /**
     * @param  array<int, string>|null  $permissions
     *
     * @throws AuthorizationException
     */
    public function execute(User $actor, string $name, ?array $permissions = null): Role
    {
        $bypassPermission = config()->string('system.permissions.bypass-authorization');

        throw_if(in_array($bypassPermission, $permissions ?? [], true) && ! $actor->checkPermissionTo($bypassPermission), AuthorizationException::class);

        return (new Role)->getConnection()->transaction(function () use ($name, $permissions): Role {
            $role = new Role(['name' => $name]);
            $role->disableLogging()->save();
            $role->enableLogging();
            $role->syncPermissions($permissions ?? []);

            RoleAuditLog::log($role, ActivityEvent::Created, [], RoleAuditLog::snapshot($role));

            return $role;
        });
    }
}
