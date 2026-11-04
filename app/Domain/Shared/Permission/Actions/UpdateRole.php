<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Support\RoleActivity;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Enums\ActivityEvent;

class UpdateRole
{
    use Actionable;

    /**
     * @param  array<int, string>|null  $permissions
     *
     * @throws AuthorizationException
     */
    public function execute(User $actor, Role $role, ?string $name = null, ?array $permissions = null): Role
    {
        return $role->getConnection()->transaction(function () use ($actor, $role, $name, $permissions): Role {
            $role->newQuery()->whereKey($role->getKey())->lockForUpdate()->first();

            $old = RoleActivity::snapshot($role);
            $current = RoleActivity::permissionNames($role);

            $this->guard($actor, $role, $current, collect($permissions ?? $current->all()));

            if ($name !== null) {
                $role->name = $name;
            }

            $role->disableLogging()->save();
            $role->enableLogging();

            if ($permissions !== null) {
                $role->syncPermissions($permissions);
            }

            RoleActivity::log($role, ActivityEvent::Updated, $old, RoleActivity::snapshot($role));

            return $role;
        });
    }

    /**
     * @param  Collection<int, string>  $current
     * @param  Collection<int, string>  $new
     *
     * @throws AuthorizationException
     */
    private function guard(User $actor, Role $role, Collection $current, Collection $new): void
    {
        $bypassPermission = config()->string('system.permissions.bypass-authorization');
        $actorHasBypass = $actor->checkPermissionTo($bypassPermission);
        $granted = $current->contains($bypassPermission);

        throw_if(! $actorHasBypass && ($granted || $new->contains($bypassPermission)), AuthorizationException::class);

        throw_if($granted && $new->doesntContain($bypassPermission) && $actor->hasRole($role), AuthorizationException::class);
    }
}
