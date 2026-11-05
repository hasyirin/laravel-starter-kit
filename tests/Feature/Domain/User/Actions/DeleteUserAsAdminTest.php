<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\DeleteUserAsAdmin;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('an admin cannot delete themselves', function (): void {
    $admin = User::factory()->create();
    grantPermissions($admin, 'bypass-authorization');

    expect(fn () => DeleteUserAsAdmin::make()->execute($admin, $admin))->toThrow(AuthorizationException::class);
    expect($admin->fresh()->trashed())->toBeFalse();
});

test('an admin without bypass cannot delete a bypass holder', function (): void {
    $admin = User::factory()->create();
    $holder = User::factory()->create();
    grantPermissions($holder, 'bypass-authorization');

    expect(fn () => DeleteUserAsAdmin::make()->execute($holder, $admin))->toThrow(AuthorizationException::class);
    expect($holder->fresh()->trashed())->toBeFalse();
});

test('an admin deletes a user', function (): void {
    $user = User::factory()->create();

    DeleteUserAsAdmin::make()->execute($user, User::factory()->create());

    expect($user->fresh()->trashed())->toBeTrue();
});

test('the signed-in user is the admin when none is given', function (): void {
    $user = User::factory()->create();
    $this->actingAs(User::factory()->create());

    DeleteUserAsAdmin::make()->execute($user);

    expect($user->fresh()->trashed())->toBeTrue();
});
