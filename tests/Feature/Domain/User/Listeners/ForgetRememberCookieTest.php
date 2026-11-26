<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * @return Collection<int, Cookie>
 */
function rememberCookiesOf(TestResponse $response): Collection
{
    return collect($response->headers->getCookies())
        ->filter(fn (Cookie $cookie): bool => str($cookie->getName())->startsWith('remember_'))
        ->values();
}

test('a password sign-in that asks to be remembered sets no remember cookie and deletes an old one', function (): void {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => '1']);

    $this->assertAuthenticatedAs($user);
    expect(rememberCookiesOf($response))->toHaveCount(1)
        ->sequence(fn ($cookie) => $cookie->isCleared()->toBeTrue());
});

test('a password sign-in without remember me deletes an old remember cookie', function (): void {
    $user = User::factory()->create();
    $recaller = auth()->guard('web')->getRecallerName();
    $passwordHash = auth()->guard('web')->hashPasswordForCookie($user->password);

    $response = $this->withCookie($recaller, "{$user->id}|remember-token|{$passwordHash}")
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
    expect(rememberCookiesOf($response))->toHaveCount(1)
        ->sequence(fn ($cookie) => $cookie->isCleared()->toBeTrue());
});

test('a registration that asks to be remembered sets no remember cookie and deletes an old one', function (): void {
    $this->enableFeature(Feature::Registration);

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'phone' => '+60123456789',
        'password' => 'password',
        'password_confirmation' => 'password',
        'remember' => '1',
    ]);

    $this->assertAuthenticated();
    expect(rememberCookiesOf($response))->toHaveCount(1)
        ->sequence(fn ($cookie) => $cookie->isCleared()->toBeTrue());
});

test('a two-factor sign-in that asked to be remembered sets no remember cookie and deletes an old one', function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);
    $user = User::factory()->withTwoFactor()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => '1']);

    $response = $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1']);

    $this->assertAuthenticatedAs($user);
    expect(rememberCookiesOf($response))->toHaveCount(1)
        ->sequence(fn ($cookie) => $cookie->isCleared()->toBeTrue());
});

test('a sign-in on another guard than the Fortify guard keeps the queued cookies', function (): void {
    $recaller = auth()->guard('web')->getRecallerName();
    cookie()->queue($recaller, 'value');

    event(new Login('other', User::factory()->create(), true));

    expect(cookie()->hasQueued($recaller))->toBeTrue();
});
