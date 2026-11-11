<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Support\Intercept;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;

test('the intercept sends to the signed-in user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(Intercept::user()?->is($user))->toBeTrue();
});

test('the intercept sends to the user who was signed in when the app queued the message, also after that user is deleted', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);
    auth()->forgetGuards();
    $user->delete();

    expect(Intercept::user()?->is($user))->toBeTrue();
});

test('the intercept sends to the impersonator during impersonation', function (): void {
    $impersonator = User::factory()->create();
    Impersonation::begin($impersonator, 'https://example.test/start');

    $this->actingAs(User::factory()->create());

    expect(Intercept::user()?->is($impersonator))->toBeTrue();
});

test('the intercept sends to the impersonator who was signed in when the app queued the message, also after that user is deleted', function (): void {
    $impersonator = User::factory()->create();
    Impersonation::begin($impersonator, 'https://example.test/start');

    $this->actingAs(User::factory()->create());
    Impersonation::end();
    auth()->forgetGuards();
    $impersonator->delete();

    expect(Intercept::user()?->is($impersonator))->toBeTrue();
});

test('the intercept has no user for a guest', function (): void {
    expect(Intercept::user())->toBeNull();
});
