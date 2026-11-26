<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\Management\DeleteOtherUser;
use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
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

test('a deleted user has all their sessions ended by the staff at the browser, also one where they impersonate someone', function (): void {
    $staff = User::factory()->create();
    $user = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user)->create();
    UserSession::factory()->impersonatedBy($user)->create();
    $staffSession = UserSession::factory()->forUser($staff)->create();
    $this->actingAs($staff);

    DeleteOtherUser::make()->execute($user);

    expect(UserSession::query()->pluck('id')->all())->toBe([$staffSession->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$staff->id, $staff->id, $staff->id]);
});

test('a deleted user has the sessions where other staff impersonate them ended by the staff who deleted them', function (): void {
    $impersonator = User::factory()->create();
    $staff = User::factory()->create();
    $user = User::factory()->create();
    UserSession::factory()->forUser($user)->impersonatedBy($impersonator)->create();
    $this->actingAs($staff);

    DeleteOtherUser::make()->execute($user);

    expect(UserSession::query()->count())->toBe(0)
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->sole()->only('user_id', 'impersonator_id', 'ended_by_id'))->toBe([
            'user_id' => $user->id,
            'impersonator_id' => $impersonator->id,
            'ended_by_id' => $staff->id,
        ]);
});

test('a staff delete that fails to end the sessions keeps the user', function (): void {
    $user = User::factory()->create();
    $this->mock(EndAllSessions::class)->shouldReceive('execute')->andThrow(new RuntimeException('Ending failed.'));

    expect(fn () => DeleteOtherUser::make()->execute($user, User::factory()->create()))->toThrow(RuntimeException::class, 'Ending failed.');

    expect($user->fresh()->trashed())->toBeFalse();
});
