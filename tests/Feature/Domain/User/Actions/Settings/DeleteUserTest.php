<?php

declare(strict_types=1);

use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Actions\Settings\DeleteUser;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;

test('a deleted user has all their sessions ended, also one where they impersonate someone', function (): void {
    $user = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user)->create();
    UserSession::factory()->impersonatedBy($user)->create();
    $other = UserSession::factory()->create();

    DeleteUser::make()->execute($user);

    expect($user->fresh()->trashed())->toBeTrue()
        ->and(UserSession::query()->pluck('id')->all())->toBe([$other->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$user->id, $user->id, $user->id]);
});

test('a deleted user has the sessions where someone impersonates them ended', function (): void {
    $user = User::factory()->create();
    UserSession::factory()->forUser($user)->impersonatedBy(User::factory()->create())->create();

    DeleteUser::make()->execute($user);

    expect(UserSession::query()->count())->toBe(0)
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$user->id]);
});

test('a self delete that fails to end the sessions keeps the user', function (): void {
    $user = User::factory()->create();
    $this->mock(EndAllSessions::class)->shouldReceive('execute')->andThrow(new RuntimeException('Ending failed.'));

    expect(fn () => DeleteUser::make()->execute($user))->toThrow(RuntimeException::class, 'Ending failed.');

    expect($user->fresh()->trashed())->toBeFalse();
});
