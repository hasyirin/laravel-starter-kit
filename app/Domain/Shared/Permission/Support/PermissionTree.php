<?php

declare(strict_types=1);

namespace App\Domain\Shared\Permission\Support;

/**
 * @phpstan-type PermissionLeaf array{name: string, label: string}
 * @phpstan-type PermissionNode array{key: string, label: string, kind: 'area'|'resource', permissions: list<PermissionLeaf>, children: list<array<string, mixed>>}
 */
class PermissionTree
{
    /**
     * @param  iterable<string>  $names
     * @return PermissionNode
     */
    public static function build(iterable $names): array
    {
        $paths = [];

        foreach ($names as $name) {
            $paths[] = ['name' => $name, 'segments' => explode('.', $name)];
        }

        return self::node('', '', $paths);
    }

    /**
     * @param  list<array{name: string, segments: non-empty-list<string>}>  $paths
     * @return PermissionNode
     */
    private static function node(string $key, string $label, array $paths): array
    {
        $permissions = [];
        $groups = [];

        foreach ($paths as $path) {
            $segments = $path['segments'];
            $segment = array_shift($segments);

            if ($segments === []) {
                $permissions[] = ['name' => $path['name'], 'label' => self::label('actions', $segment)];
            } else {
                $groups[$segment][] = ['name' => $path['name'], 'segments' => $segments];
            }
        }

        $children = [];

        foreach ($groups as $segment => $group) {
            $segment = (string) $segment;
            $children[] = self::node(ltrim("$key.$segment", '.'), self::label('segments', $segment), $group);
        }

        return [
            'key' => $key,
            'label' => $label,
            'kind' => $children === [] ? 'resource' : 'area',
            'permissions' => $permissions,
            'children' => $children,
        ];
    }

    private static function label(string $group, string $segment): string
    {
        $key = "user/permissions.$group.$segment";
        $label = __($key);

        return is_string($label) && $label !== $key ? $label : str($segment)->headline()->lower()->ucfirst()->value();
    }
}
