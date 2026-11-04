<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Support;

use App\Domain\Shared\Permission\Models\Role;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Support\ChangeDetector;

class RoleActivity
{
    /**
     * @return array<array-key, mixed>
     */
    public static function snapshot(Role $role): array
    {
        return [...Role::extractChanges($role), 'permissions' => self::permissionNames($role)->all()];
    }

    /**
     * @return Collection<int, string>
     */
    public static function permissionNames(Role $role): Collection
    {
        return $role->unsetRelation('permissions')
            ->getPermissionNames()
            ->map(fn (mixed $name): string => (string) $name)
            ->sort()
            ->values();
    }

    /**
     * @param  array<array-key, mixed>  $old
     * @param  array<array-key, mixed>  $new
     */
    public static function log(Role $role, ActivityEvent $event, array $old, array $new): void
    {
        $dirty = ChangeDetector::filterDirty($new, $old);
        $changes = $event === ActivityEvent::Created ? ['attributes' => $new] : ['attributes' => $dirty['attributes'], 'old' => $dirty['old']];

        if ($dirty['attributes'] === []) {
            return;
        }

        activity()
            ->performedOn($role)
            ->event($event->value)
            ->withChanges($changes)
            ->log($event->value);
    }
}
