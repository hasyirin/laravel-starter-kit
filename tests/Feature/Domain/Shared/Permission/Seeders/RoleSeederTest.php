<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use Tests\Fixtures\Seeders\AuditorRoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('seeding creates only the super admin role, with the bypass-authorization permission', function (): void {
    $this->seed(RoleSeeder::class);

    expect(Role::query()->pluck('name')->all())->toBe(['super-admin'])
        ->and(Role::findByName('super-admin')->is_system)->toBeTrue()
        ->and(Role::findByName('super-admin')->permissions->pluck('name')->all())->toBe(['bypass-authorization']);
});

test('seeding leaves out permissions that match an except pattern', function (): void {
    $this->seed(AuditorRoleSeeder::class);

    expect(Role::findByName('auditor')->permissions->pluck('name')->sort()->values()->all())->toBe([
        'admin.access',
        'admin.controls.roles.create',
        'admin.controls.roles.update',
        'admin.controls.roles.update-permissions',
        'admin.controls.roles.view',
        'admin.controls.roles.view-any',
        'admin.controls.users.create',
        'admin.controls.users.update',
        'admin.controls.users.view',
        'admin.controls.users.view-any',
    ]);
});

test('seeding keeps permissions that were given to a role later', function (): void {
    $this->seed(RoleSeeder::class);
    Role::findByName('super-admin')->givePermissionTo('admin.access');

    $this->seed(RoleSeeder::class);

    expect(Role::findByName('super-admin')->hasPermissionTo('admin.access'))->toBeTrue();
});

test('seeding reuses an existing role with the same name', function (): void {
    Role::query()->create(['name' => 'super-admin']);

    $this->seed(RoleSeeder::class);

    expect(Role::query()->where('name', 'super-admin')->count())->toBe(1)
        ->and(Role::findByName('super-admin')->is_system)->toBeTrue()
        ->and(Role::findByName('super-admin')->permissions)->toHaveCount(1);
});
