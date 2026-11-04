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

    $this->user->givePermissionTo('admin.controls.users.view-any');

    expect($this->user->can('viewAny', User::class))->toBeTrue();
});

test('view needs the view-any or view permission', function (string $permission): void {
    expect($this->user->can('view', $this->other))->toBeFalse();

    $this->user->givePermissionTo($permission);

    expect($this->user->can('view', $this->other))->toBeTrue();
})->with(['admin.controls.users.view-any', 'admin.controls.users.view']);

test('a user can view their own account', function (): void {
    expect($this->user->can('view', $this->user))->toBeTrue();
});

test('create needs the create permission', function (): void {
    expect($this->user->can('create', User::class))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.users.create');

    expect($this->user->can('create', User::class))->toBeTrue();
});

test('creating with a type needs create and the update permission for that type', function (string $type): void {
    $this->user->givePermissionTo('admin.controls.users.create');

    expect($this->user->can('create', [User::class, $type]))->toBeFalse();

    $this->user->revokePermissionTo('admin.controls.users.create');
    $this->user->givePermissionTo("admin.controls.users.update-{$type}");

    expect($this->user->can('create', [User::class, $type]))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.users.create');

    expect($this->user->can('create', [User::class, $type]))->toBeTrue();
})->with(['roles', 'permissions']);

test('update needs the update permission', function (): void {
    expect($this->user->can('update', $this->other))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.users.update');

    expect($this->user->can('update', $this->other))->toBeTrue();
});

test('updating a type needs the update permission for that type', function (string $type): void {
    $this->user->givePermissionTo('admin.controls.users.update');

    expect($this->user->can('update', [$this->other, $type]))->toBeFalse();

    $this->user->givePermissionTo("admin.controls.users.update-{$type}");

    expect($this->user->can('update', [$this->other, $type]))->toBeTrue();
})->with(['roles', 'permissions']);

test('delete needs the delete permission', function (): void {
    expect($this->user->can('delete', $this->other))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.users.delete');

    expect($this->user->can('delete', $this->other))->toBeTrue();
});

test('a user cannot change their own roles or permissions', function (string $type): void {
    $this->user->givePermissionTo(['admin.controls.users.update', "admin.controls.users.update-{$type}"]);

    expect($this->user->can('update', [$this->user, $type]))->toBeFalse()
        ->and($this->user->can('update', [$this->other, $type]))->toBeTrue();
})->with(['roles', 'permissions']);

test('a user with the bypass-authorization permission can change their own roles and permissions', function (string $type): void {
    $this->user->givePermissionTo('bypass-authorization');

    expect($this->user->can('update', [$this->user, $type]))->toBeTrue();
})->with(['roles', 'permissions']);

test('a user cannot delete themselves from the admin list', function (): void {
    $this->user->givePermissionTo('admin.controls.users.delete');

    expect($this->user->can('delete', $this->user))->toBeFalse()
        ->and($this->user->can('delete', $this->other))->toBeTrue();
});

test('only a user with bypass-authorization can delete a user who has it', function (): void {
    $this->user->givePermissionTo('admin.controls.users.delete');
    $this->other->givePermissionTo('bypass-authorization');

    expect($this->user->can('delete', $this->other))->toBeFalse();

    $this->user->givePermissionTo('bypass-authorization');

    expect($this->user->can('delete', $this->other))->toBeTrue();
});

test('only a user with bypass-authorization can update a user who has it', function (?string $type): void {
    $this->user->givePermissionTo('admin.controls.users.update', 'admin.controls.users.update-roles', 'admin.controls.users.update-permissions');
    $this->other->givePermissionTo('bypass-authorization');

    expect($this->user->can('update', [$this->other, $type]))->toBeFalse();

    $this->user->givePermissionTo('bypass-authorization');

    expect($this->user->can('update', [$this->other, $type]))->toBeTrue();
})->with(['details' => [null], 'roles' => ['roles'], 'permissions' => ['permissions']]);

test('a role that carries bypass-authorization puts its holder out of reach of an update', function (): void {
    $this->seed(RoleSeeder::class);
    $this->user->givePermissionTo('admin.controls.users.update');
    Profile::factory()->for($this->other)->withRoles('super-admin')->create();

    expect($this->user->can('update', $this->other->fresh()))->toBeFalse();
});

test('viewing the activities of a user needs view-activities', function (): void {
    $this->user->givePermissionTo('admin.controls.users.view');

    expect($this->user->can('view', [$this->other, 'activities']))->toBeFalse()
        ->and($this->user->can('view', [$this->user, 'activities']))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.users.view-activities');

    expect($this->user->can('view', [$this->other, 'activities']))->toBeTrue()
        ->and($this->user->can('view', [$this->user, 'activities']))->toBeTrue();
});

test('a typed ability needs the base ability and the typed permission', function (): void {
    $this->user->givePermissionTo('admin.controls.users.view-activities');

    expect($this->user->can('view', [$this->other, 'activities']))->toBeFalse();

    $this->user->givePermissionTo('admin.controls.users.view');

    expect($this->user->can('view', [$this->other, 'activities']))->toBeTrue();
});
