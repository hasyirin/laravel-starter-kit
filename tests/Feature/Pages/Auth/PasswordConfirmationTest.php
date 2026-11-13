<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;

test('confirm password screen can be rendered', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertOk();
});

test('the confirm password screen shows the passkey button when passkeys are on', function (): void {
    $this->skipUnlessFeature(Feature::Passkeys);

    $this->actingAs(User::factory()->create())->get(route('password.confirm'))->assertOk()->assertSee(__('user/passkeys.confirm.button'));
});

test('the confirm password screen opens without the passkey button when passkeys are off', function (): void {
    $this->disableFeature(Feature::Passkeys);

    $this->actingAs(User::factory()->create())->get(route('password.confirm'))->assertOk()->assertDontSee(__('user/passkeys.confirm.button'));
});

test('password confirmation takes the impersonator password while impersonating', function (): void {
    $impersonator = User::factory()->create(['password' => 'impersonator-password']);
    Impersonation::begin($impersonator, 'https://example.test/start');

    $response = $this->actingAs(User::factory()->create())->post(route('password.confirm.store'), ['password' => 'impersonator-password']);

    $response->assertSessionHasNoErrors();

    expect(session()->has('auth.password_confirmed_at'))->toBeTrue();
});

test('password confirmation refuses the impersonated user password while impersonating', function (): void {
    Impersonation::begin(User::factory()->create(['password' => 'impersonator-password']), 'https://example.test/start');

    $response = $this->actingAs(User::factory()->create())->post(route('password.confirm.store'), ['password' => 'password']);

    $response->assertSessionHasErrors('password');
});

test('password confirmation takes the user own password otherwise', function (): void {
    $response = $this->actingAs(User::factory()->create())->post(route('password.confirm.store'), ['password' => 'password']);

    $response->assertSessionHasNoErrors();
});
