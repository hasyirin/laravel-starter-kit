<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

test('a user has the permissions of their active profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->withPermissions(Permission::query()->create(['name' => 'system.access']))->create();

    expect($user->can('system.access'))->toBeTrue();
});

test('a user does not have the permissions of an inactive profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->withPermissions(Permission::query()->create(['name' => 'system.access']))->create();
    Profile::factory()->for($user)->create();

    expect($user->can('system.access'))->toBeFalse();
});

test('the permission helpers and scopes count the direct grants and the active profile', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::query()->create(['name' => 'reports.view']));
    Profile::factory()->for($user)->withPermissions(Permission::query()->create(['name' => 'system.access']))->create();
    $other = User::factory()->create();

    expect($user->can('reports.view'))->toBeTrue()
        ->and($user->can('system.access'))->toBeTrue()
        ->and($user->getAllPermissions()->pluck('name')->all())->toEqualCanonicalizing(['system.access', 'reports.view'])
        ->and($user->getPermissionNames()->all())->toEqualCanonicalizing(['system.access', 'reports.view'])
        ->and($user->getDirectPermissions()->pluck('name')->all())->toEqualCanonicalizing(['system.access', 'reports.view'])
        ->and($user->hasDirectPermission('reports.view'))->toBeTrue()
        ->and($user->hasAllDirectPermissions('system.access', 'reports.view'))->toBeTrue()
        ->and(User::query()->permission('system.access')->pluck('id')->all())->toBe([$user->id])
        ->and(User::query()->permission('reports.view')->pluck('id')->all())->toBe([$user->id])
        ->and(User::query()->withoutPermission('system.access')->pluck('id')->all())->toBe([$other->id])
        ->and(User::query()->withoutPermission('reports.view')->pluck('id')->all())->toBe([$other->id]);
});

test('the permissions through roles count the direct grants and the active profile', function (): void {
    $user = User::factory()->create();
    $writer = Role::query()->create(['name' => 'writer']);
    $writer->givePermissionTo(Permission::query()->create(['name' => 'reports.view']));

    $user->assignRole($writer);
    $editor = Role::query()->create(['name' => 'editor']);
    $editor->givePermissionTo(Permission::query()->create(['name' => 'system.access']));
    Profile::factory()->for($user)->withRoles($editor)->create();

    expect($user->getPermissionsViaRoles()->pluck('name')->all())->toEqualCanonicalizing(['system.access', 'reports.view']);
});

test('a user can get and lose permissions directly', function (string $method, string $permission, array $expected): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::query()->create(['name' => 'reports.view']));
    Permission::query()->create(['name' => 'system.access']);

    $user->{$method}($permission);

    expect($user->fresh()?->getPermissionNames()->all())->toEqualCanonicalizing($expected);
})->with([
    ['givePermissionTo', 'system.access', ['system.access', 'reports.view']],
    ['syncPermissions', 'system.access', ['system.access']],
    ['revokePermissionTo', 'reports.view', []],
]);
