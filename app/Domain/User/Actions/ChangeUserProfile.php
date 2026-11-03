<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ChangeUserProfile
{
    use Actionable;

    private const array PLACEMENT_KEYS = ['organization_id', 'branch_id', 'department_id', 'position_id'];

    /**
     * @param  array{organization_id: ?int, branch_id: ?int, department_id: ?int, position_id: ?int}|null  $placement
     * @param  array<int, string>|null  $roles
     * @param  array<int, string>|null  $permissions
     *
     * @throws AuthorizationException
     */
    public function execute(User $actor, User $user, ?array $placement = null, ?array $roles = null, ?array $permissions = null): ?Profile
    {
        return $user->getConnection()->transaction(function () use ($actor, $user, $placement, $roles, $permissions): ?Profile {
            $user->newQueryWithoutRelationships()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            $user->unsetRelation('profile');

            $actorHasBypass = $actor->checkPermissionTo($this->bypassPermission());

            throw_if(! $actorHasBypass && $user->checkPermissionTo($this->bypassPermission()), AuthorizationException::class);

            $current = $user->profile()->first();
            $currentPlacement = $this->placementOf($current?->only(self::PLACEMENT_KEYS) ?? []);
            $currentRoles = $this->normalized($current?->getRoleNames());
            $currentPermissions = $this->normalized($current?->getPermissionNames());

            $newPlacement = $this->placementOf($placement ?? $currentPlacement);
            $newRoles = $this->normalized(collect($roles ?? $currentRoles->all()));

            $newPermissions = $this->normalized(collect($permissions ?? $currentPermissions->all()));

            $bypassRoles = $this->normalized(Role::query()
                ->whereRelation('permissions', 'name', $this->bypassPermission())
                ->pluck('name'));

            $isSelf = $actor->is($user);
            $this->guardProtected($actorHasBypass, $isSelf, $newRoles, $currentRoles, $bypassRoles);
            $this->guardProtected($actorHasBypass, $isSelf, $newPermissions, $currentPermissions, collect([$this->bypassPermission()]));

            $isUnchanged = $current instanceof Profile
                && $newPlacement === $currentPlacement
                && $newRoles->all() === $currentRoles->all()
                && $newPermissions->all() === $currentPermissions->all();

            if ($isUnchanged) {
                return null;
            }

            $profile = $user->profiles()
                ->make($newPlacement)
                ->syncRoles($newRoles->all())
                ->syncPermissions($newPermissions->all());

            $profile->save();

            $user->unsetRelation('profile');

            return $profile;
        });
    }

    /**
     * @param  Collection<int, string>  $new
     * @param  Collection<int, string>  $current
     * @param  Collection<int, string>  $protected
     *
     * @throws AuthorizationException
     */
    private function guardProtected(bool $actorHasBypass, bool $isSelf, Collection $new, Collection $current, Collection $protected): void
    {
        $added = $new->intersect($protected)->diff($current);
        $removed = $current->intersect($protected)->diff($new);

        throw_if($added->isNotEmpty() && ! $actorHasBypass, AuthorizationException::class);

        throw_if($removed->isNotEmpty() && $isSelf, AuthorizationException::class);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, ?int>
     */
    private function placementOf(array $values): array
    {
        return collect(self::PLACEMENT_KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => $this->idOf($values[$key] ?? null)])
            ->all();
    }

    private function idOf(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param  Collection<TKey, TValue>|null  $names
     * @return Collection<int, string>
     */
    private function normalized(?Collection $names): Collection
    {
        return collect($names)
            ->map(fn (mixed $name): string => (string) $name)
            ->unique()
            ->sort()
            ->values();
    }

    private function bypassPermission(): string
    {
        return config()->string('system.permissions.bypass-authorization');
    }
}
