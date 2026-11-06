<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;

test('the authenticated user id is added to the context', function (): void {
    $user = User::factory()->create();

    expect(context()->get('user_id'))->toBeNull();

    $this->actingAs($user);

    expect(context()->get('user_id'))->toBe($user->id);
});

test('the impersonator id is added to the context while impersonating', function (): void {
    $impersonator = User::factory()->create();
    Impersonation::begin($impersonator, 'https://example.test/start');

    $this->actingAs(User::factory()->create());

    expect(context()->get('impersonator_id'))->toBe($impersonator->id);
});

test('no impersonator id is added to the context otherwise', function (): void {
    $this->actingAs(User::factory()->create());

    expect(context()->has('impersonator_id'))->toBeFalse();
});
