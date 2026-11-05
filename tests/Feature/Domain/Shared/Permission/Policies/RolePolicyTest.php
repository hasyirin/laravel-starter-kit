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

    grantPermissions($this->user, 'internal.controls.roles.view-any');

    expect($this->user->can('viewAny', Role::class))->toBeTrue();
});

test('view needs the view-any or view permission', function (string $permission): void {
    expect($this->user->can('view', $this->role))->toBeFalse();

    grantPermissions($this->user, $permission);

    expect($this->user->can('view', $this->role))->toBeTrue();
})->with(['internal.controls.roles.view-any', 'internal.controls.roles.view']);

test('having a role does not let a user view it', function (): void {
    grantRoles($this->user, $this->role);

    expect($this->user->can('view', $this->role))->toBeFalse();
});

test('viewing a type needs the view permission for that type', function (): void {
    grantPermissions($this->user, 'internal.controls.roles.view');

    expect($this->user->can('view', [$this->role, 'audit-log']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.roles.view-audit-log');

    expect($this->user->can('view', [$this->role, 'audit-log']))->toBeTrue();
});

test('create needs the create permission', function (): void {
    expect($this->user->can('create', Role::class))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.roles.create');

    expect($this->user->can('create', Role::class))->toBeTrue();
});

test('creating a type needs the update permission for that type', function (): void {
    grantPermissions($this->user, 'internal.controls.roles.create');

    expect($this->user->can('create', [Role::class, 'permissions']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.roles.update-permissions');

    expect($this->user->can('create', [Role::class, 'permissions']))->toBeTrue();
});

test('update needs the update permission', function (): void {
    expect($this->user->can('update', $this->role))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.roles.update');

    expect($this->user->can('update', $this->role))->toBeTrue();
});

test('the name of a system role cannot be updated', function (): void {
    grantPermissions($this->user, 'internal.controls.roles.update');

    expect($this->user->can('update', [$this->role, 'name']))->toBeTrue()
        ->and($this->user->can('update', [$this->system, 'name']))->toBeFalse();
});

test('updating a type needs the update permission for that type', function (): void {
    grantPermissions($this->user, 'internal.controls.roles.update');

    expect($this->user->can('update', [$this->role, 'permissions']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.roles.update-permissions');

    expect($this->user->can('update', [$this->role, 'permissions']))->toBeTrue();
});

test('delete needs the delete permission', function (): void {
    expect($this->user->can('delete', $this->role))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.roles.delete');

    expect($this->user->can('delete', $this->role))->toBeTrue();
});

test('a system role cannot be deleted', function (): void {
    grantPermissions($this->user, 'internal.controls.roles.delete');

    expect($this->user->can('delete', $this->system))->toBeFalse();
});
