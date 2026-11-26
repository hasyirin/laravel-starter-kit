<?php

declare(strict_types=1);

use App\Domain\User\Actions\Security\UpdatePassword;
use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use App\Domain\User\Support\Impersonation;

test('a password change ends the other sessions of the user and keeps the current one', function (): void {
    $user = User::factory()->create();
    $current = UserSession::factory()->forUser($user)->create();
    UserSession::factory()->count(2)->forUser($user)->create();
    $this->actingAs($user);
    session()->put(UserSession::SESSION_KEY, $current->id);

    UpdatePassword::make()->execute($user, 'new-password');

    expect(UserSession::query()->pluck('id')->all())->toBe([$current->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$user->id, $user->id]);
});

test('a password change of an impersonator ends their impersonation session elsewhere', function (): void {
    $impersonator = User::factory()->create();
    $current = UserSession::factory()->forUser($impersonator)->create();
    $impersonation = UserSession::factory()->impersonatedBy($impersonator)->create();
    $this->actingAs($impersonator);
    session()->put(UserSession::SESSION_KEY, $current->id);

    UpdatePassword::make()->execute($impersonator, 'new-password');

    expect(UserSession::query()->pluck('id')->all())->toBe([$current->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->sole()->only('user_id', 'impersonator_id', 'ended_by_id'))->toBe([
            'user_id' => $impersonation->user_id,
            'impersonator_id' => $impersonator->id,
            'ended_by_id' => $impersonator->id,
        ]);
});

test('a password change during an impersonation names the impersonator as who ended the sessions', function (): void {
    $impersonator = User::factory()->create();
    $user = User::factory()->create();
    UserSession::factory()->count(2)->forUser($user)->create();
    $current = UserSession::factory()->forUser($user)->impersonatedBy($impersonator)->create();
    $this->actingAs($user);
    Impersonation::begin($impersonator, 'https://example.test/start');
    session()->put(UserSession::SESSION_KEY, $current->id);

    UpdatePassword::make()->execute($user, 'new-password');

    expect(UserSession::query()->pluck('id')->all())->toBe([$current->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$impersonator->id, $impersonator->id]);
});

test('a password change that fails to end the sessions keeps the old password', function (): void {
    $user = User::factory()->create();
    $oldHash = $user->password;
    $this->mock(EndAllSessions::class)->shouldReceive('execute')->andThrow(new RuntimeException('Ending failed.'));

    expect(fn () => UpdatePassword::make()->execute($user, 'new-password'))->toThrow(RuntimeException::class, 'Ending failed.');

    expect($user->fresh()->password)->toBe($oldHash);
});
