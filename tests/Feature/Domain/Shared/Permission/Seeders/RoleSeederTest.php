<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use Tests\Fixtures\Seeders\AuditorRoleSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('seeding creates the admin role with every admin permission', function (): void {
    $this->seed(RoleSeeder::class);

    $admin = Role::findByName('admin');

    expect($admin->is_system)->toBeTrue()
        ->and($admin->permissions)->toHaveCount(14)
        ->and($admin->hasPermissionTo('bypass-authorization'))->toBeFalse();
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
    Role::findByName('admin')->givePermissionTo('bypass-authorization');

    $this->seed(RoleSeeder::class);

    expect(Role::findByName('admin')->hasPermissionTo('bypass-authorization'))->toBeTrue();
});

test('seeding reuses an existing role with the same name', function (): void {
    Role::query()->create(['name' => 'admin']);

    $this->seed(RoleSeeder::class);

    expect(Role::query()->where('name', 'admin')->count())->toBe(1)
        ->and(Role::findByName('admin')->is_system)->toBeTrue()
        ->and(Role::findByName('admin')->permissions)->toHaveCount(14);
});
