<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('a user with the bypass-authorization permission and no role passes every ability', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    grantPermissions($user, 'bypass-authorization');

    expect($user->roles)->toBeEmpty()
        ->and($user->can('anything'))->toBeTrue();
});

test('a user without the bypass-authorization permission does not pass every ability', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    grantRoles($user, Role::query()->create(['name' => 'editor']));

    expect($user->can('anything'))->toBeFalse()
        ->and($user->can('system.access'))->toBeFalse();
});

test('a user with a role gets the permissions of that role', function (): void {
    $this->seed(PermissionSeeder::class);

    $role = Role::query()->create(['name' => 'editor']);
    $role->givePermissionTo('system.access');

    $user = User::factory()->create();
    grantRoles($user, $role);

    expect($user->can('system.access'))->toBeTrue()
        ->and($user->can('admin.controls.users.delete'))->toBeFalse();
});

test('ability checks do not fail when the bypass-authorization permission does not exist', function (): void {
    expect(User::factory()->create()->can('anything'))->toBeFalse();
});
