<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\Management\DeleteOtherUser;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('an actor cannot delete themselves', function (): void {
    $actor = User::factory()->create();
    grantPermissions($actor, 'bypass-authorization');

    expect(fn () => DeleteOtherUser::make()->execute($actor, $actor))->toThrow(AuthorizationException::class);
    expect($actor->fresh()->trashed())->toBeFalse();
});

test('an actor without bypass cannot delete a bypass holder', function (): void {
    $actor = User::factory()->create();
    $holder = User::factory()->create();
    grantPermissions($holder, 'bypass-authorization');

    expect(fn () => DeleteOtherUser::make()->execute($holder, $actor))->toThrow(AuthorizationException::class);
    expect($holder->fresh()->trashed())->toBeFalse();
});

test('an actor deletes another user', function (): void {
    $user = User::factory()->create();

    DeleteOtherUser::make()->execute($user, User::factory()->create());

    expect($user->fresh()->trashed())->toBeTrue();
});

test('the signed-in user is the actor when none is given', function (): void {
    $user = User::factory()->create();
    $this->actingAs(User::factory()->create());

    DeleteOtherUser::make()->execute($user);

    expect($user->fresh()->trashed())->toBeTrue();
});
