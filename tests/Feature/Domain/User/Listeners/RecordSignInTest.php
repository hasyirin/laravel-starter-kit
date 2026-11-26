<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use Illuminate\Auth\Events\Login;

test('a password sign-in records one sign-in entry with the password method', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'two_factor', 'user_id', 'impersonator_id'))->toBe([
        'event' => SignInEvent::SignIn,
        'result' => SignInResult::SignedIn,
        'method' => SignInMethod::Password,
        'two_factor' => false,
        'user_id' => $user->id,
        'impersonator_id' => null,
    ]);
});

test('the sign-in right after registration records one sign-in entry with the password method', function (): void {
    $this->enableFeature(Feature::Registration);

    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'phone' => '+60123456789',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'test@example.com')->sole();
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'two_factor', 'user_id'))->toBe([
        'event' => SignInEvent::SignIn,
        'result' => SignInResult::SignedIn,
        'method' => SignInMethod::Password,
        'two_factor' => false,
        'user_id' => $user->id,
    ]);
});

test('a right password records nothing while the two-factor challenge is pending', function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));

    expect(SignInEntry::query()->count())->toBe(0);
});

test('a passed two-factor challenge records one sign-in entry with the password method and two-factor', function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1']);

    $this->assertAuthenticatedAs($user);
    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'two_factor', 'user_id'))->toBe([
        'event' => SignInEvent::SignIn,
        'result' => SignInResult::SignedIn,
        'method' => SignInMethod::Password,
        'two_factor' => true,
        'user_id' => $user->id,
    ]);
});

test('a "remember me" renewal records no sign-in entry', function (): void {
    $user = User::factory()->create(['remember_token' => 'remember-token']);
    $recaller = auth()->guard('web')->getRecallerName();

    $this->withCookie($recaller, "{$user->id}|remember-token|{$user->password}")->get(route('settings'));

    $this->assertAuthenticatedAs($user);
    expect(auth()->guard('web')->viaRemember())->toBeTrue()
        ->and(SignInEntry::query()->count())->toBe(0);
});

test('a sign-in on a route with no sign-in method records an entry without a method', function (): void {
    $user = User::factory()->create();

    auth()->guard('web')->login($user);

    expect(SignInEntry::query()->sole()->only('event', 'result', 'method', 'user_id'))->toBe([
        'event' => SignInEvent::SignIn,
        'result' => SignInResult::SignedIn,
        'method' => null,
        'user_id' => $user->id,
    ]);
});

test('a sign-in on another guard than the Fortify guard records nothing', function (): void {
    event(new Login('other', User::factory()->create(), false));

    expect(SignInEntry::query()->count())->toBe(0);
});
