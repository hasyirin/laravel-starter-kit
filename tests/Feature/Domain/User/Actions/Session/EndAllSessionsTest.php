<?php

declare(strict_types=1);

use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;

test('ending all sessions ends the sessions the user owns except the kept one, and counts them', function (): void {
    $user = User::factory()->create();
    $staff = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user)->create();
    $kept = UserSession::factory()->forUser($user)->create();
    $other = UserSession::factory()->create();

    $count = EndAllSessions::make()->execute($user, $staff, except: $kept);

    expect($count)->toBe(2)
        ->and(UserSession::query()->pluck('id')->sort()->values()->all())->toBe(collect([$kept->id, $other->id])->sort()->values()->all())
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->where('ended_by_id', $staff->id)->pluck('user_id')->all())->toBe([$user->id, $user->id]);
});

test('ending all sessions ends a session where the user is the impersonator, and keeps one where the user is impersonated', function (): void {
    $user = User::factory()->create();
    $asImpersonator = UserSession::factory()->impersonatedBy($user)->create();
    $asImpersonated = UserSession::factory()->forUser($user)->impersonatedBy(User::factory()->create())->create();

    $count = EndAllSessions::make()->execute($user);

    expect($count)->toBe(1)
        ->and(UserSession::query()->pluck('id')->all())->toBe([$asImpersonated->id])
        ->and(SignInEntry::query()->sole()->only('user_id', 'impersonator_id', 'ended_by_id'))->toBe([
            'user_id' => $asImpersonator->user_id,
            'impersonator_id' => $user->id,
            'ended_by_id' => null,
        ]);
});

test('ending all sessions with the impersonated ones also ends a session where someone impersonates the user', function (): void {
    $user = User::factory()->create();
    $staff = User::factory()->create();
    UserSession::factory()->forUser($user)->create();
    UserSession::factory()->forUser($user)->impersonatedBy(User::factory()->create())->create();
    $other = UserSession::factory()->create();

    $count = EndAllSessions::make()->execute($user, $staff, includeImpersonated: true);

    expect($count)->toBe(2)
        ->and(UserSession::query()->pluck('id')->all())->toBe([$other->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$staff->id, $staff->id]);
});

test('ending all sessions with the impersonated ones still keeps the kept session', function (): void {
    $user = User::factory()->create();
    $kept = UserSession::factory()->forUser($user)->create();
    UserSession::factory()->forUser($user)->create();
    UserSession::factory()->forUser($user)->impersonatedBy(User::factory()->create())->create();

    $count = EndAllSessions::make()->execute($user, except: $kept, includeImpersonated: true);

    expect($count)->toBe(2)
        ->and(UserSession::query()->pluck('id')->all())->toBe([$kept->id]);
});
