<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Permission;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

test('a user has the permissions of their active profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->withPermissions(Permission::query()->create(['name' => 'admin.access']))->create();

    expect($user->can('admin.access'))->toBeTrue();
});

test('a user does not have the permissions of an inactive profile', function (): void {
    $user = User::factory()->create();
    Profile::factory()->for($user)->withPermissions(Permission::query()->create(['name' => 'admin.access']))->create();
    Profile::factory()->for($user)->create();

    expect($user->can('admin.access'))->toBeFalse();
});

test('the permission helpers and scopes count only the permissions of the active profile', function (): void {
    $user = User::factory()->create();
    $user->permissions()->attach(Permission::query()->create(['name' => 'reports.view']));
    Profile::factory()->for($user)->withPermissions(Permission::query()->create(['name' => 'admin.access']))->create();
    $other = User::factory()->create();

    expect($user->can('reports.view'))->toBeFalse()
        ->and($user->getAllPermissions()->pluck('name')->all())->toBe(['admin.access'])
        ->and($user->getPermissionNames()->all())->toBe(['admin.access'])
        ->and($user->getDirectPermissions()->pluck('name')->all())->toBe(['admin.access'])
        ->and($user->hasDirectPermission('reports.view'))->toBeFalse()
        ->and($user->hasAnyDirectPermission('reports.view'))->toBeFalse()
        ->and($user->hasAllDirectPermissions('admin.access'))->toBeTrue()
        ->and(User::query()->permission('admin.access')->pluck('id')->all())->toBe([$user->id])
        ->and(User::query()->permission('reports.view')->pluck('id')->all())->toBe([])
        ->and(User::query()->withoutPermission('admin.access')->pluck('id')->all())->toBe([$other->id]);
});

test('the permissions through roles come only from the roles of the active profile', function (): void {
    $user = User::factory()->create();
    $writer = Role::query()->create(['name' => 'writer']);
    $writer->givePermissionTo(Permission::query()->create(['name' => 'reports.view']));
    $user->roles()->attach($writer);
    $editor = Role::query()->create(['name' => 'editor']);
    $editor->givePermissionTo(Permission::query()->create(['name' => 'admin.access']));
    Profile::factory()->for($user)->withRoles($editor)->create();

    expect($user->getPermissionsViaRoles()->pluck('name')->all())->toBe(['admin.access']);
});

test('the permissions of a user change only through a profile', function (string $method): void {
    $user = User::factory()->create();
    Permission::query()->create(['name' => 'admin.access']);

    expect(fn (): mixed => $user->{$method}('admin.access'))->toThrow(LogicException::class, 'Users get roles and permissions only through a profile.')
        ->and($user->permissions()->exists())->toBeFalse();
})->with(['givePermissionTo', 'syncPermissions', 'revokePermissionTo']);
