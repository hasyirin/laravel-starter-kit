<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use Laravel\Passkeys\Passkey;

dataset('sign-in method routes', [
    ['two-factor.enable', 'POST', Feature::TwoFactorAuthentication, 200],
    ['two-factor.confirm', 'POST', Feature::TwoFactorAuthentication, 422],
    ['two-factor.disable', 'DELETE', Feature::TwoFactorAuthentication, 200],
    ['two-factor.qr-code', 'GET', Feature::TwoFactorAuthentication, 200],
    ['two-factor.secret-key', 'GET', Feature::TwoFactorAuthentication, 404],
    ['two-factor.recovery-codes', 'GET', Feature::TwoFactorAuthentication, 200],
    ['two-factor.regenerate-recovery-codes', 'POST', Feature::TwoFactorAuthentication, 200],
    ['passkey.registration-options', 'GET', Feature::Passkeys, 200],
    ['passkey.store', 'POST', Feature::Passkeys, 422],
    ['passkey.destroy', 'DELETE', Feature::Passkeys, 200],
]);

function signInMethodPasskey(User $user): Passkey
{
    return $user->passkeys()->create([
        'name' => 'Work laptop',
        'credential_id' => str()->random(32),
        'credential' => ['id' => 'credential'],
    ]);
}

function signInMethodRouteUrl(string $route, Passkey $passkey): string
{
    if ($route === 'passkey.destroy') {
        return route($route, $passkey);
    }

    return route($route);
}

test("a user with an unverified email address or phone number is forbidden on each Fortify route for the user's own sign-in methods", function (string $route, string $method, Feature $feature, int $status, User $user, Feature $verification): void {
    $this->skipUnlessFeature($feature);
    $this->skipUnlessFeature($verification);

    $passkey = signInMethodPasskey($user);
    $url = signInMethodRouteUrl($route, $passkey);
    $this->actingAs($user);

    $this->json($method, $url)->assertForbidden();

    $this->withSession(['auth.password_confirmed_at' => time()])
        ->json($method, $url)
        ->assertForbidden();

    expect($user->refresh()->two_factor_secret)->toBeNull();
    $this->assertModelExists($passkey);
})->with('sign-in method routes')->with([
    'an unverified email address' => [fn (): User => User::factory()->unverified()->create(), Feature::EmailVerification],
    'an unverified phone number' => [fn (): User => User::factory()->unverifiedPhone()->create(), Feature::PhoneVerification],
]);

test("a verified user needs only the password step on each Fortify route for the user's own sign-in methods", function (string $route, string $method, Feature $feature, int $status): void {
    $this->skipUnlessFeature($feature);
    $this->configureFeature(Feature::TwoFactorAuthentication, [
        'confirm' => true,
        'confirmPassword' => true,
    ]);
    $this->configureFeature(Feature::Passkeys, [
        'confirmPassword' => true,
    ]);
    $this->enableFeature($feature);

    $user = User::factory()->create();
    $url = signInMethodRouteUrl($route, signInMethodPasskey($user));
    $this->actingAs($user);

    $this->json($method, $url)->assertStatus(423);

    $this->withSession(['auth.password_confirmed_at' => time()])
        ->json($method, $url)
        ->assertStatus($status);
})->with('sign-in method routes');

test("a Fortify route for the user's own sign-in methods needs a verified email address also when the password step is off", function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);
    $this->skipUnlessFeature(Feature::EmailVerification);
    $this->configureFeature(Feature::TwoFactorAuthentication, ['confirmPassword' => false]);
    $this->enableFeature(Feature::TwoFactorAuthentication);

    $this->actingAs(User::factory()->unverified()->create())
        ->postJson(route('two-factor.enable'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->postJson(route('two-factor.enable'))
        ->assertOk();
});
