<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Permission\Models\Role;
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
     *
     * @throws AuthorizationException
     */
    public function execute(User $actor, User $user, ?array $placement = null, ?array $roles = null, ?array $permissions = null): ?Profile
    {
        return $user->getConnection()->transaction(function () use ($actor, $user, $placement, $roles, $permissions): ?Profile {
            $user->newQueryWithoutRelationships()->whereKey($user->getKey())->lockForUpdate()->first();
            $user->unsetRelation('profile');
            $actorHasBypass = $actor->checkPermissionTo($this->bypassPermission());

            throw_if(! $actorHasBypass && $user->checkPermissionTo($this->bypassPermission()), AuthorizationException::class);

            $current = $user->profile()->first();

            $currentPlacement = $this->placementOf($current?->only(self::PLACEMENT_KEYS) ?? []);
            /** @var array<int, string> $roleNames */
            $roleNames = $current?->getRoleNames()->all() ?? [];
            /** @var array<int, string> $permissionNames */
            $permissionNames = $current?->getPermissionNames()->all() ?? [];
            $currentRoles = $this->normalized($roleNames);
            $currentPermissions = $this->normalized($permissionNames);

            $placement = $placement === null ? $currentPlacement : $this->placementOf($placement);
            $isSelf = $actor->is($user);
            $roles = $this->guardedSet($actorHasBypass, $isSelf, $roles ?? $currentRoles, $currentRoles, $this->bypassRoles());
            $permissions = $this->guardedSet($actorHasBypass, $isSelf, $permissions ?? $currentPermissions, $currentPermissions, [$this->bypassPermission()]);

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
     * @param  list<string>  $protected
     * @return list<string>
     *
     * @throws AuthorizationException
     */
    private function guardedSet(bool $actorHasBypass, bool $isSelf, array $given, array $current, array $protected): array
    {
        $held = array_intersect($current, $protected);

        $given = $this->normalized($actorHasBypass ? $given : [...$given, ...$held]);

        $added = array_diff(array_intersect($given, $protected), $current);
        $removed = array_diff($held, $given);

        throw_if(($added !== [] || $removed !== []) && ! $actorHasBypass, AuthorizationException::class);

        throw_if($removed !== [] && $isSelf, AuthorizationException::class);

        return $given;
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

    /**
     * @return list<string>
     */
    private function bypassRoles(): array
    {
        return $this->normalized(Role::query()->with('permissions')->get()
            ->filter(fn (Role $role): bool => $role->carries_bypass)
            ->map(fn (Role $role): string => $role->name)
            ->all());
    }

    private function bypassPermission(): string
    {
        return config()->string('system.permissions.bypass-authorization');
    }
}
