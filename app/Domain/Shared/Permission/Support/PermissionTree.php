<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Support;

use Illuminate\Support\Collection;

/**
 * @phpstan-type PermissionLeaf array{name: string, label: string}
 * @phpstan-type PermissionNode array{key: string, label: string, kind: 'group'|'resource', permissions: array<int, PermissionLeaf>, children: array<int, array<string, mixed>>}
 * @phpstan-type PermissionPath array{name: string, rest: string}
 */
class PermissionTree
{
    private const string GROUP_ABILITY = 'access';

    /**
     * @return Collection<int, string>
     */
    public static function configured(): Collection
    {
        return collect(config('system.permissions'))
            ->flatten()
            ->map(fn (mixed $name): string => (string) $name)
            ->values();
    }

    /**
     * @param  iterable<int, string>  $names
     * @return PermissionNode
     */
    public static function build(iterable $names): array
    {
        $paths = collect($names)->map(fn (string $name): array => ['name' => $name, 'rest' => $name]);

        return self::node('', '', $paths);
    }

    /**
     * @param  Collection<array-key, PermissionPath>  $paths
     * @return PermissionNode
     */
    private static function node(string $key, string $label, Collection $paths): array
    {
        $abilities = $paths->filter(self::isAbility(...));
        $groups = $paths->reject(self::isAbility(...));

        $permissions = $abilities
            ->map(fn (array $path): array => ['name' => $path['name'], 'label' => self::label('abilities', $path['rest'])])
            ->values()
            ->all();

        $children = $groups
            ->groupBy(fn (array $path): string => str($path['rest'])->before('.')->value())
            ->map(fn (Collection $group, int|string $segment): array => self::child($key, (string) $segment, $group))
            ->values()
            ->all();

        return [
            'key' => $key,
            'label' => $label,
            'kind' => self::kind($children, $abilities),
            'permissions' => $permissions,
            'children' => $children,
        ];
    }

    /**
     * @param  Collection<array-key, PermissionPath>  $paths
     * @return PermissionNode
     */
    private static function child(string $parentKey, string $segment, Collection $paths): array
    {
        $rest = $paths->map(fn (array $path): array => ['name' => $path['name'], 'rest' => str($path['rest'])->after('.')->value()]);

        return self::node(ltrim("$parentKey.$segment", '.'), self::label('segments', $segment), $rest);
    }

    /**
     * @param  PermissionPath  $path
     */
    private static function isAbility(array $path): bool
    {
        return ! str_contains($path['rest'], '.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @param  Collection<array-key, PermissionPath>  $abilities
     * @return 'group'|'resource'
     */
    private static function kind(array $children, Collection $abilities): string
    {
        if ($children !== []) {
            return 'group';
        }

        $groupOnly = $abilities->every(fn (array $path): bool => $path['rest'] === self::GROUP_ABILITY);

        if ($groupOnly) {
            return 'group';
        }

        return 'resource';
    }

    private static function label(string $group, string $segment): string
    {
        $key = "user/permissions.$group.$segment";
        $label = __($key);

        if (! is_string($label) || $label === $key) {
            return str($segment)->headline()->lower()->ucfirst()->value();
        }

        return $label;
    }
}
