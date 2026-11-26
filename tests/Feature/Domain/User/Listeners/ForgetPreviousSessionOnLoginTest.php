<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Illuminate\Auth\Events\Login;

test('a sign-in after a session whose row is gone starts its own row and stays signed in', function (): void {
    $user = User::factory()->create();
    session()->put(UserSession::SESSION_KEY, 999);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk();

    $this->assertAuthenticatedAs($user);
    expect(UserSession::query()->sole()->user_id)->toBe($user->id);
});

test('a sign-in after the session of another user deletes that row and starts its own', function (): void {
    $this->freezeSecond();
    $previous = UserSession::factory()->create(['signed_in_at' => now()->subDays(10)]);
    $previous->user?->delete();
    session()->put(UserSession::SESSION_KEY, $previous->id);
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $session = UserSession::withTrashed()->sole();
    expect($session->id)->not->toBe($previous->id)
        ->and($session->user_id)->toBe($user->id)
        ->and($session->signed_in_at->equalTo(now()))->toBeTrue()
        ->and(session(UserSession::SESSION_KEY))->toBe($session->id);
});

test('a sign-in on another guard than the Fortify guard keeps the session row', function (): void {
    $session = UserSession::factory()->create();
    session()->put(UserSession::SESSION_KEY, $session->id);

    event(new Login('other', User::factory()->create(), false));

    expect(UserSession::query()->sole()->is($session))->toBeTrue()
        ->and(session(UserSession::SESSION_KEY))->toBe($session->id);
});
