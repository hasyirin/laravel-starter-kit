<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->role = Role::query()->create(['name' => 'editor']);
    $this->system = Role::query()->forceCreate(['name' => 'admin', 'guard_name' => 'web', 'is_system' => true]);
});

test('view any needs the view-any permission', function (): void {
    expect($this->user->can('viewAny', Role::class))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.roles.view-any');

    expect($this->user->can('viewAny', Role::class))->toBeTrue();
});

test('view needs the view-any or view permission', function (string $permission): void {
    expect($this->user->can('view', $this->role))->toBeFalse();

    $this->user->givePermissionTo($permission);

    expect($this->user->can('view', $this->role))->toBeTrue();
})->with(['admin.controls.roles.view-any', 'admin.controls.roles.view']);

test('a user can view a role they have', function (): void {
    $this->user->assignRole($this->role);

    expect($this->user->can('view', $this->role))->toBeTrue()
        ->and($this->user->can('view', $this->system))->toBeFalse();
});

test('create needs the create permission', function (): void {
    expect($this->user->can('create', Role::class))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.roles.create');

    expect($this->user->can('create', Role::class))->toBeTrue();
});

test('update needs the update permission', function (): void {
    expect($this->user->can('update', $this->role))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.roles.update');

    expect($this->user->can('update', $this->role))->toBeTrue();
});

test('the name of a system role cannot be updated', function (): void {
    $this->user->givePermissionTo('admin.controls.roles.update');

    expect($this->user->can('update', [$this->role, 'name']))->toBeTrue()
        ->and($this->user->can('update', [$this->system, 'name']))->toBeFalse();
});

test('updating a type needs the update permission for that type', function (): void {
    $this->user->givePermissionTo('admin.controls.roles.update');

    expect($this->user->can('update', [$this->role, 'permissions']))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.roles.update-permissions');

    expect($this->user->can('update', [$this->role, 'permissions']))->toBeTrue();
});

test('delete needs the delete permission', function (): void {
    expect($this->user->can('delete', $this->role))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.roles.delete');

    expect($this->user->can('delete', $this->role))->toBeTrue();
});

test('a system role cannot be deleted', function (): void {
    $this->user->givePermissionTo('admin.controls.roles.delete');

    expect($this->user->can('delete', $this->system))->toBeFalse();
});
