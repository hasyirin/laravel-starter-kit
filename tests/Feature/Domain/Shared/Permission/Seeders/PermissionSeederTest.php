<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;

test('seeding creates every permission in the config', function (): void {
    $configuredPermissionCount = collect(config('system.permissions'))->flatten()->count();

    $this->seed(PermissionSeeder::class);

    expect(Permission::query()->count())->toBe($configuredPermissionCount)
        ->and(Permission::query()->whereIn('name', ['bypass-authorization', 'internal.access', 'admin.access', 'admin.controls.users.delete'])->count())->toBe(4);
});

test('seeding twice does not duplicate permissions', function (): void {
    $configuredPermissionCount = collect(config('system.permissions'))->flatten()->count();

    $this->seed(PermissionSeeder::class);
    $this->seed(PermissionSeeder::class);

    expect(Permission::query()->count())->toBe($configuredPermissionCount);
});

test('seeding removes permissions that are no longer in the config', function (): void {
    $role = Role::query()->create(['name' => 'editor']);
    $role->givePermissionTo(Permission::query()->create(['name' => 'posts.publish']));

    $this->seed(PermissionSeeder::class);

    expect(Permission::query()->where('name', 'posts.publish')->exists())->toBeFalse()
        ->and($role->fresh()?->permissions)->toBeEmpty();
});
