<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->other = User::factory()->create();
});

test('view any needs the view-any permission', function (): void {
    expect($this->user->can('viewAny', User::class))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.view-any');

    expect($this->user->can('viewAny', User::class))->toBeTrue();
});

test('view needs the view-any or view permission', function (string $permission): void {
    expect($this->user->can('view', $this->other))->toBeFalse();

    grantPermissions($this->user, $permission);

    expect($this->user->can('view', $this->other))->toBeTrue();
})->with(['internal.controls.users.view-any', 'internal.controls.users.view']);

test('a user can view their own account', function (): void {
    expect($this->user->can('view', $this->user))->toBeTrue();
});

test('create needs the create permission', function (): void {
    expect($this->user->can('create', User::class))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.create');

    expect($this->user->can('create', User::class))->toBeTrue();
});

test('creating with a type needs create and the update permission for that type', function (string $type): void {
    grantPermissions($this->user, 'internal.controls.users.create');

    expect($this->user->can('create', [User::class, $type]))->toBeFalse();

    revokePermissions($this->user, 'internal.controls.users.create');
    grantPermissions($this->user, "internal.controls.users.update-{$type}");

    expect($this->user->can('create', [User::class, $type]))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.create');

    expect($this->user->can('create', [User::class, $type]))->toBeTrue();
})->with(['roles', 'permissions']);

test('update needs the update permission', function (): void {
    expect($this->user->can('update', $this->other))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.update');

    expect($this->user->can('update', $this->other))->toBeTrue();
});

test('updating a type needs the update permission for that type', function (string $type): void {
    grantPermissions($this->user, 'internal.controls.users.update');

    expect($this->user->can('update', [$this->other, $type]))->toBeFalse();

    grantPermissions($this->user, "internal.controls.users.update-{$type}");

    expect($this->user->can('update', [$this->other, $type]))->toBeTrue();
})->with(['roles', 'permissions']);

test('delete needs the delete permission', function (): void {
    expect($this->user->can('delete', $this->other))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.delete');

    expect($this->user->can('delete', $this->other))->toBeTrue();
});

test('a user cannot change their own roles or permissions', function (string $type): void {
    grantPermissions($this->user, ['internal.controls.users.update', "internal.controls.users.update-{$type}"]);

    expect($this->user->can('update', [$this->user, $type]))->toBeFalse()
        ->and($this->user->can('update', [$this->other, $type]))->toBeTrue();
})->with(['roles', 'permissions']);

test('a user with the bypass-authorization permission can change their own roles and permissions', function (string $type): void {
    grantPermissions($this->user, 'bypass-authorization');

    expect($this->user->can('update', [$this->user, $type]))->toBeTrue();
})->with(['roles', 'permissions']);

test('a user cannot delete themselves from the user list', function (): void {
    grantPermissions($this->user, 'internal.controls.users.delete');

    expect($this->user->can('delete', $this->user))->toBeFalse()
        ->and($this->user->can('delete', $this->other))->toBeTrue();
});

test('only a user with bypass-authorization can delete a user who has it', function (): void {
    grantPermissions($this->user, 'internal.controls.users.delete');
    grantPermissions($this->other, 'bypass-authorization');

    expect($this->user->can('delete', $this->other))->toBeFalse();

    grantPermissions($this->user, 'bypass-authorization');

    expect($this->user->can('delete', $this->other))->toBeTrue();
});

test('only a user with bypass-authorization can update a user who has it', function (?string $type): void {
    grantPermissions($this->user, 'internal.controls.users.update', 'internal.controls.users.update-roles', 'internal.controls.users.update-permissions');
    grantPermissions($this->other, 'bypass-authorization');

    expect($this->user->can('update', [$this->other, $type]))->toBeFalse();

    grantPermissions($this->user, 'bypass-authorization');

    expect($this->user->can('update', [$this->other, $type]))->toBeTrue();
})->with(['details' => [null], 'roles' => ['roles'], 'permissions' => ['permissions']]);

test('a role that carries bypass-authorization puts its holder out of reach of an update', function (): void {
    $this->seed(RoleSeeder::class);
    grantPermissions($this->user, 'internal.controls.users.update');
    Profile::factory()->for($this->other)->withRoles('system-admin')->create();

    expect($this->user->can('update', $this->other->fresh()))->toBeFalse();
});

test('viewing the audit log of a user needs view-audit-log', function (): void {
    grantPermissions($this->user, 'internal.controls.users.view');

    expect($this->user->can('view', [$this->other, 'audit-log']))->toBeFalse()
        ->and($this->user->can('view', [$this->user, 'audit-log']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.view-audit-log');

    expect($this->user->can('view', [$this->other, 'audit-log']))->toBeTrue()
        ->and($this->user->can('view', [$this->user, 'audit-log']))->toBeTrue();
});

test('a typed ability needs the base ability and the typed permission', function (): void {
    grantPermissions($this->user, 'internal.controls.users.view-audit-log');

    expect($this->user->can('view', [$this->other, 'audit-log']))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.view');

    expect($this->user->can('view', [$this->other, 'audit-log']))->toBeTrue();
});

test('impersonate needs the impersonate and update permissions', function (): void {
    expect($this->user->can('impersonate', $this->other))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.impersonate');

    expect($this->user->can('impersonate', $this->other))->toBeFalse();

    revokePermissions($this->user, 'internal.controls.users.impersonate');
    grantPermissions($this->user, 'internal.controls.users.update');

    expect($this->user->can('impersonate', $this->other))->toBeFalse();

    grantPermissions($this->user, 'internal.controls.users.impersonate');

    expect($this->user->can('impersonate', $this->other))->toBeTrue();
});

test('a user cannot impersonate themselves', function (): void {
    grantPermissions($this->user, 'internal.controls.users.impersonate', 'internal.controls.users.update');

    expect($this->user->can('impersonate', $this->user))->toBeFalse();
});

test('a user cannot impersonate a deleted user', function (): void {
    grantPermissions($this->user, 'internal.controls.users.impersonate', 'internal.controls.users.update');

    $this->other->delete();

    expect($this->user->can('impersonate', $this->other))->toBeFalse();
});

test('a user without bypass-authorization cannot impersonate a user who has it', function (): void {
    grantPermissions($this->user, 'internal.controls.users.impersonate', 'internal.controls.users.update');
    grantPermissions($this->other, 'bypass-authorization');

    expect($this->user->can('impersonate', $this->other))->toBeFalse();
});

test('nobody can impersonate while impersonating', function (): void {
    grantPermissions($this->user, 'internal.controls.users.impersonate', 'internal.controls.users.update');

    session()->put('impersonation.impersonator_id', $this->other->id);

    expect($this->user->can('impersonate', $this->other))->toBeFalse();
});
