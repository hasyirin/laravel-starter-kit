<?php

declare(strict_types=1);

namespace App\Domain\Shared\Documentation\Sections;

use App\Domain\Shared\Documentation\Contracts\Section;
use App\Domain\Shared\Documentation\Support\MarkdownTable;
use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;

class PermissionMatrix implements Section
{
    public function render(array $options): string
    {
        $bypass = config()->string('system.permissions.bypass-authorization');
        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $bypasses = fn (Role $role): bool => $role->permissions->contains('name', $bypass);

        $rows = Permission::query()->orderBy('id')->get()->map(fn (Permission $permission): array => [
            "`{$permission->name}`",
            ...$roles->map(fn (Role $role): string => $bypasses($role) || $role->permissions->contains('name', $permission->name) ? '✓' : '')->all(),
        ])->all();

        $table = MarkdownTable::render(['Permission', ...$roles->map(fn (Role $role): string => $role->label)->all()], $rows);

        return $roles->contains($bypasses) ? "{$table}\n\nA role with `{$bypass}` passes every check." : $table;
    }
}
