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
        'internal.access',
        'internal.controls.letterheads.create',
        'internal.controls.letterheads.update',
        'internal.controls.letterheads.view',
        'internal.controls.letterheads.view-any',
        'internal.controls.organizations.create',
        'internal.controls.organizations.update',
        'internal.controls.organizations.view',
        'internal.controls.organizations.view-any',
        'internal.controls.organizations.view-audit-log',
        'internal.controls.roles.create',
        'internal.controls.roles.update',
        'internal.controls.roles.update-permissions',
        'internal.controls.roles.view',
        'internal.controls.roles.view-any',
        'internal.controls.roles.view-audit-log',
        'internal.controls.users.create',
        'internal.controls.users.impersonate',
        'internal.controls.users.update',
        'internal.controls.users.view',
        'internal.controls.users.view-any',
        'internal.controls.users.view-audit-log',
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
