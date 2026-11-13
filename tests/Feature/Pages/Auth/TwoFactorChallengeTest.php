<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);
});

test('two factor challenge redirects to login when not authenticated', function (): void {
    $response = $this->get(route('two-factor.login'));

    $response->assertRedirect(route('login'));
});

test('two factor challenge can be rendered', function (): void {
    $this->configureFeature(Feature::TwoFactorAuthentication, [
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));
});

test('users who pass the two factor challenge go to the highest area they can open', function (): void {
    $this->configureFeature(Feature::TwoFactorAuthentication, [
        'confirm' => true,
        'confirmPassword' => true,
    ]);
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->withTwoFactor()->create();
    grantPermissions($user, 'internal.access');

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->post(route('two-factor.login.store'), [
        'recovery_code' => $user->recoveryCodes()[0],
    ]);

    $response->assertRedirect(route('internal.dashboard'));
});
