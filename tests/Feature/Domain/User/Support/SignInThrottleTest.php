<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use Illuminate\Testing\TestResponse;

function assertLockedOutResponse(TestResponse $response, int $limit): void
{
    $response->assertTooManyRequests()
        ->assertHeader('Retry-After', '60')
        ->assertHeader('X-RateLimit-Limit', (string) $limit)
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertHeader('X-RateLimit-Reset', (string) now()->addMinute()->getTimestamp());
}

test('a locked-out sign-in keeps the 429 response and records one locked-out entry of the user that the typed email address names', function (): void {
    $this->freezeTime();
    $user = User::factory()->create(['email' => 'locked@example.com']);
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'locked@example.com', 'password' => 'wrong-password']);
    }

    $response = $this->post(route('login.store'), ['email' => 'LOCKED@example.com', 'password' => 'wrong-password']);

    assertLockedOutResponse($response, 5);
    expect(SignInEntry::query()->where('result', SignInResult::LockedOut)->sole()->only('event', 'method', 'two_factor', 'user_id'))->toBe([
        'event' => SignInEvent::FailedSignIn,
        'method' => SignInMethod::Password,
        'two_factor' => false,
        'user_id' => $user->id,
    ]);
});

test('a locked-out sign-in with no matching user records a locked-out entry without a user or the typed email address', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
    }

    $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password'])->assertTooManyRequests();

    $entry = SignInEntry::query()->where('result', SignInResult::LockedOut)->sole();
    expect($entry->user_id)->toBeNull()
        ->and(json_encode($entry->getAttributes()))->not->toContain('nobody@example.com');
});

test('a locked-out two-factor challenge keeps the 429 response and records one locked-out entry of the challenged user', function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);
    $this->freezeTime();
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    foreach (range(1, 5) as $attempt) {
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'wrong-recovery-code']);
    }

    $response = $this->post(route('two-factor.login.store'), ['recovery_code' => 'wrong-recovery-code']);

    assertLockedOutResponse($response, 5);
    expect(SignInEntry::query()->where('result', SignInResult::LockedOut)->sole()->only('event', 'method', 'two_factor', 'user_id'))->toBe([
        'event' => SignInEvent::FailedSignIn,
        'method' => SignInMethod::Password,
        'two_factor' => true,
        'user_id' => $user->id,
    ]);
});

test('a locked-out passkey sign-in keeps the 429 response and records one locked-out entry without a user', function (string $route, string $method): void {
    $this->skipUnlessFeature(Feature::Passkeys);
    $this->freezeTime();
    $this->withCredentials()->withCookie(config()->string('session.cookie'), str_repeat('a', 40));
    foreach (range(1, 10) as $attempt) {
        $this->json($method, route($route));
    }

    $response = $this->json($method, route($route));

    assertLockedOutResponse($response, 10);
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'user_id'))->toBe([
        'event' => SignInEvent::FailedSignIn,
        'result' => SignInResult::LockedOut,
        'method' => SignInMethod::Passkey,
        'user_id' => null,
    ]);
})->with([
    'the sign-in options' => ['passkey.login-options', 'GET'],
    'the sign-in' => ['passkey.login', 'POST'],
]);

test('a locked-out passkey confirmation keeps the 429 response and records nothing', function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);
    $this->freezeTime();
    $this->actingAs(User::factory()->create());
    $this->withCredentials()->withCookie(config()->string('session.cookie'), str_repeat('a', 40));
    foreach (range(1, 10) as $attempt) {
        $this->getJson(route('passkey.confirm-options'));
    }

    $response = $this->getJson(route('passkey.confirm-options'));

    assertLockedOutResponse($response, 10);
    expect(SignInEntry::query()->count())->toBe(0);
});

test('a sign-in that is throttled again in the same window keeps the 429 response and records no second locked-out entry', function (): void {
    $this->freezeTime();
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
    }

    $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password'])->assertTooManyRequests();
    $this->travel(10)->seconds();

    $response = $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password']);

    $response->assertTooManyRequests()
        ->assertHeader('Retry-After', '50')
        ->assertHeader('X-RateLimit-Limit', '5')
        ->assertHeader('X-RateLimit-Remaining', '0')
        ->assertHeader('X-RateLimit-Reset', (string) now()->addSeconds(50)->getTimestamp());
    expect(SignInEntry::query()->where('result', SignInResult::LockedOut)->count())->toBe(1);
});

test('a sign-in that is throttled again after the window passed records a new locked-out entry', function (): void {
    $this->freezeTime();
    foreach (range(1, 6) as $attempt) {
        $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
    }

    $this->travel(61)->seconds();
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password']);
    }

    $response = $this->post(route('login.store'), ['email' => 'nobody@example.com', 'password' => 'wrong-password']);

    assertLockedOutResponse($response, 5);
    expect(SignInEntry::query()->where('result', SignInResult::LockedOut)->count())->toBe(2);
});

test('a locked-out passkey sign-in records the user of the passkey that the request names, or no user for an unknown passkey', function (bool $known): void {
    $this->skipUnlessFeature(Feature::Passkeys);
    $this->freezeTime();
    $user = User::factory()->create();
    $user->passkeys()->create(['name' => 'Work laptop', 'credential_id' => 'known-credential', 'credential' => ['id' => 'known-credential']]);
    $body = ['credential' => ['id' => $known ? 'known-credential' : 'unknown-credential']];
    foreach (range(1, 10) as $attempt) {
        $this->postJson(route('passkey.login'), $body);
    }

    $response = $this->postJson(route('passkey.login'), $body);

    assertLockedOutResponse($response, 10);
    expect(SignInEntry::query()->sole()->only('result', 'method', 'user_id'))->toBe([
        'result' => SignInResult::LockedOut,
        'method' => SignInMethod::Passkey,
        'user_id' => $known ? $user->id : null,
    ]);
})->with([
    'a known passkey' => [true],
    'an unknown passkey' => [false],
]);
