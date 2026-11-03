<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Support;

use Illuminate\Support\Collection;

/**
 * @phpstan-type PermissionLeaf array{name: string, label: string}
 * @phpstan-type PermissionNode array{key: string, label: string, kind: 'area'|'resource', permissions: array<int, PermissionLeaf>, children: array<int, array<string, mixed>>}
 * @phpstan-type PermissionPath array{name: string, rest: string}
 */
class PermissionTree
{
    private const string GROUP_ACTION = 'access';

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
        $actions = $paths->filter(self::isAction(...));
        $groups = $paths->reject(self::isAction(...));

        $permissions = $actions
            ->map(fn (array $path): array => ['name' => $path['name'], 'label' => self::label('actions', $path['rest'])])
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
            'kind' => self::kind($children, $actions),
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
    private static function isAction(array $path): bool
    {
        return ! str_contains($path['rest'], '.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @param  Collection<array-key, PermissionPath>  $actions
     * @return 'area'|'resource'
     */
    private static function kind(array $children, Collection $actions): string
    {
        if ($children !== []) {
            return 'area';
        }

        $hasActions = $actions->contains(fn (array $path): bool => $path['rest'] !== self::GROUP_ACTION);

        if (! $hasActions) {
            return 'area';
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
