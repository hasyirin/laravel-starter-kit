<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('a user with an unverified phone number goes to the phone verification page', function (string $route): void {
    $this->skipUnlessFeature(Feature::PhoneVerification);

    $user = User::factory()->unverifiedPhone()->create();
    grantPermissions($user, 'internal.access', 'system.access');
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    $this->get(route($route))->assertRedirect(route('phone-verification.notice'));
})->with(['dashboard', 'appearance.edit', 'security.edit', 'internal.dashboard', 'system.dashboard']);

test('user information and preferences stay open to a user with an unverified phone number', function (string $route): void {
    $this->actingAs(User::factory()->unverifiedPhone()->create());

    $this->get(route($route))->assertOk();
})->with(['user-information.edit', 'preferences.edit']);

test('a user with a verified phone number passes', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
});

test('a holder of bypass-authorization passes without a phone number', function (string $route): void {
    $user = User::factory()->withoutPhone()->create();
    grantPermissions($user, 'bypass-authorization');
    $this->actingAs($user);

    $this->get(route($route))->assertOk();
})->with(['dashboard', 'system.dashboard']);

test('a json request with an unverified phone number is forbidden', function (): void {
    $this->skipUnlessFeature(Feature::PhoneVerification);

    $this->actingAs(User::factory()->unverifiedPhone()->create());

    $this->getJson(route('dashboard'))->assertForbidden();
});

test('the email check comes before the phone check', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $this->actingAs(User::factory()->unverified()->unverifiedPhone()->create());

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
});

test('a user with an unverified phone number passes when phone verification is off', function (): void {
    $this->disableFeature(Feature::PhoneVerification, Feature::SmsPasswordReset);
    $this->actingAs(User::factory()->unverifiedPhone()->create());

    $this->get(route('dashboard'))->assertOk();
});
