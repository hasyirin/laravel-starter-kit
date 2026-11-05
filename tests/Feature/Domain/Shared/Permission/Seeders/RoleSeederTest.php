<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use Tests\Fixtures\Seeders\AuditorRoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('seeding creates only the system-admin role, with the bypass-authorization permission', function (): void {
    $this->seed(RoleSeeder::class);

    expect(Role::query()->pluck('name')->all())->toBe(['system-admin'])
        ->and(Role::findByName('system-admin')->is_system)->toBeTrue()
        ->and(Role::findByName('system-admin')->permissions->pluck('name')->all())->toBe(['bypass-authorization']);
});

test('seeding leaves out permissions that match an except pattern', function (): void {
    $this->seed(AuditorRoleSeeder::class);

    expect(Role::findByName('auditor')->permissions->pluck('name')->sort()->values()->all())->toBe([
        'admin.controls.roles.create',
        'admin.controls.roles.update',
        'admin.controls.roles.update-permissions',
        'admin.controls.roles.view',
        'admin.controls.roles.view-any',
        'admin.controls.roles.view-audit-log',
        'admin.controls.users.create',
        'admin.controls.users.update',
        'admin.controls.users.view',
        'admin.controls.users.view-any',
        'admin.controls.users.view-audit-log',
        'system.access',
    ]);
});

test('seeding keeps permissions that were given to a role later', function (): void {
    $this->seed(RoleSeeder::class);
    Role::findByName('system-admin')->givePermissionTo('system.access');

    $this->seed(RoleSeeder::class);

    expect(Role::findByName('system-admin')->hasPermissionTo('system.access'))->toBeTrue();
});

test('seeding reuses an existing role with the same name', function (): void {
    Role::query()->create(['name' => 'system-admin']);

    $this->seed(RoleSeeder::class);

    expect(Role::query()->where('name', 'system-admin')->count())->toBe(1)
        ->and(Role::findByName('system-admin')->is_system)->toBeTrue()
        ->and(Role::findByName('system-admin')->permissions)->toHaveCount(1);
});
