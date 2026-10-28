<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
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
