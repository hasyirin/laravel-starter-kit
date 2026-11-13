<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('login screen can be rendered', function (): void {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('the login screen opens without the passkey button when passkeys are off', function (): void {
    $this->disableFeature(Feature::Passkeys);

    $this->get(route('login'))->assertOk()->assertDontSee(__('user/passkeys.verify.button'));
});

test('a guest can open the login screen more than five times a minute, because the limit of the guest forms skips it', function (): void {
    foreach (range(1, 6) as $attempt) {
        $this->get(route('login'))->assertOk();
    }
});

test('users can authenticate using the login screen', function (): void {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users go to the highest area they can open after login', function (array $permissions, string $dashboard): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, ...$permissions);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route($dashboard));
})->with([
    'with internal and system access' => [['internal.access', 'system.access'], 'system.dashboard'],
    'with system access only' => [['system.access'], 'system.dashboard'],
    'with internal access only' => [['internal.access'], 'internal.dashboard'],
]);

test('users go to the page they asked for before login', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    grantPermissions($user, 'system.access');
    $this->get(route('user-information.edit'));

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('user-information.edit'));
});

test('users can not authenticate with invalid password', function (): void {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);

    $this->configureFeature(Feature::TwoFactorAuthentication, [
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
