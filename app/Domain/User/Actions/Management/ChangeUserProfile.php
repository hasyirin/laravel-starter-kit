<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Management;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ChangeUserProfile
{
    use Actionable;

    /**
     * @param  array<int, string>|null  $roles
     * @param  array<int, string>|null  $permissions
     *
     * @throws AuthorizationException
     */
    public function execute(User $actor, User $user, ?Placement $placement = null, ?array $roles = null, ?array $permissions = null): ?Profile
    {
        return $user->getConnection()->transaction(function () use ($actor, $user, $placement, $roles, $permissions): ?Profile {
            $user->newQueryWithoutRelationships()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            $user->unsetRelation('profile');

            $actorBypasses = $actor->checkPermissionTo($this->bypassPermission());

            throw_if(! $actorBypasses && $user->checkPermissionTo($this->bypassPermission()), AuthorizationException::class);

            $current = $user->profile()->first();
            $currentPlacement = Placement::of($current);
            $currentRoles = $this->normalized($current?->getRoleNames());
            $currentPermissions = $this->normalized($current?->getPermissionNames());

            $newPlacement = $placement ?? $currentPlacement;
            $newRoles = $this->normalized(collect($roles ?? $currentRoles->all()));

            $newPermissions = $this->normalized(collect($permissions ?? $currentPermissions->all()));

            $bypassRoles = $this->normalized(Role::query()
                ->whereRelation('permissions', 'name', $this->bypassPermission())
                ->pluck('name'));

            $self = $actor->is($user);
            $this->guardProtected($actorBypasses, $self, $newRoles, $currentRoles, $bypassRoles);
            $this->guardProtected($actorBypasses, $self, $newPermissions, $currentPermissions, collect([$this->bypassPermission()]));

            $unchanged = $current instanceof Profile
                && $newPlacement->toArray() === $currentPlacement->toArray()
                && $newRoles->all() === $currentRoles->all()
                && $newPermissions->all() === $currentPermissions->all();

            if ($unchanged) {
                return null;
            }

            $profile = $user->profiles()
                ->make($newPlacement->toArray())
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
    private function guardProtected(bool $actorBypasses, bool $self, Collection $new, Collection $current, Collection $protected): void
    {
        $added = $new->intersect($protected)->diff($current);
        $removed = $current->intersect($protected)->diff($new);

        throw_if($added->isNotEmpty() && ! $actorBypasses, AuthorizationException::class);

        throw_if($removed->isNotEmpty() && $self, AuthorizationException::class);
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
