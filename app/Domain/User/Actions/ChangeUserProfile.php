<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ChangeUserProfile
{
    use Actionable;

    private const array PLACEMENT_KEYS = ['organization_id', 'branch_id', 'department_id', 'position_id'];

    /**
     * @param  array{organization_id: ?int, branch_id: ?int, department_id: ?int, position_id: ?int}|null  $placement
     * @param  list<string>|null  $roles
     * @param  list<string>|null  $permissions
     */
    public function execute(User $actor, User $user, ?array $placement = null, ?array $roles = null, ?array $permissions = null): ?Profile
    {
        return $user->getConnection()->transaction(function () use ($actor, $user, $placement, $roles, $permissions): ?Profile {
            $user->unsetRelation('profile');
            $current = $user->profile()->first();

            $currentPlacement = $this->placementOf($current?->only(self::PLACEMENT_KEYS) ?? []);
            /** @var array<int, string> $roleNames */
            $roleNames = $current?->getRoleNames()->all() ?? [];
            /** @var array<int, string> $permissionNames */
            $permissionNames = $current?->getPermissionNames()->all() ?? [];
            $currentRoles = $this->normalized($roleNames);
            $currentPermissions = $this->normalized($permissionNames);

            $placement = $placement === null ? $currentPlacement : $this->placementOf($placement);
            $roles = $this->guardedSet($actor, $user, $roles ?? $currentRoles, $currentRoles, $this->superAdminRole());
            $permissions = $this->guardedSet($actor, $user, $permissions ?? $currentPermissions, $currentPermissions, $this->bypassPermission());

            if ($current instanceof Profile
                && $placement === $currentPlacement
                && $roles === $currentRoles
                && $permissions === $currentPermissions) {
                return null;
            }

            $profile = $user->profiles()->make($placement)->syncRoles($roles)->syncPermissions($permissions);
            $profile->save();

            $user->unsetRelation('profile');

            return $profile;
        });
    }

    /**
     * @param  list<string>  $given
     * @param  list<string>  $current
     * @return list<string>
     *
     * @throws AuthorizationException
     */
    private function guardedSet(User $actor, User $user, array $given, array $current, string $protected): array
    {
        $hadProtected = in_array($protected, $current, true);
        $actorHasBypass = $actor->checkPermissionTo($this->bypassPermission());

        if ($hadProtected && ! $actorHasBypass) {
            $given[] = $protected;
        }

        $keepsProtected = in_array($protected, $given, true);

        throw_if($hadProtected !== $keepsProtected && ! $actorHasBypass, AuthorizationException::class);

        throw_if($hadProtected && ! $keepsProtected && $actor->is($user), AuthorizationException::class);

        return $this->normalized($given);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, ?int>
     */
    private function placementOf(array $values): array
    {
        return collect(self::PLACEMENT_KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => isset($values[$key]) && is_numeric($values[$key]) ? (int) $values[$key] : null])
            ->all();
    }

    /**
     * @param  array<array-key, string>  $names
     * @return list<string>
     */
    private function normalized(array $names): array
    {
        $names = array_unique($names);
        sort($names);

        return $names;
    }

    private function superAdminRole(): string
    {
        return config()->string('system.roles.super-admin');
    }

    private function bypassPermission(): string
    {
        return config()->string('system.permissions.bypass-authorization');
    }
}
