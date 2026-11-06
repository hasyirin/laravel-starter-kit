<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;

test('beginning an impersonation stores the impersonator and the start url', function (): void {
    $impersonator = User::factory()->create();

    Impersonation::begin($impersonator, 'https://example.test/start');

    expect(Impersonation::active())->toBeTrue()
        ->and(Impersonation::impersonatorId())->toBe($impersonator->id)
        ->and(Impersonation::impersonator()?->is($impersonator))->toBeTrue()
        ->and(Impersonation::startUrl())->toBe('https://example.test/start');
});

test('ending an impersonation forgets the impersonator and the start url', function (): void {
    Impersonation::begin(User::factory()->create(), 'https://example.test/start');

    Impersonation::end();

    expect(Impersonation::active())->toBeFalse()
        ->and(Impersonation::impersonatorId())->toBeNull()
        ->and(Impersonation::startUrl())->toBeNull();
});

test('a deleted impersonator is not found', function (): void {
    $impersonator = User::factory()->create();
    Impersonation::begin($impersonator, 'https://example.test/start');

    $impersonator->delete();

    expect(Impersonation::impersonator())->toBeNull();
});

test('impersonation is allowed only by the policy and never to yourself, a deleted user, or while impersonating, also with bypass', function (): void {
    $this->seed(PermissionSeeder::class);
    $impersonator = User::factory()->create();
    $user = User::factory()->create();

    expect(Impersonation::allows($impersonator, $user))->toBeFalse();

    grantPermissions($impersonator, 'bypass-authorization');

    expect(Impersonation::allows($impersonator, $user))->toBeTrue()
        ->and(Impersonation::allows($impersonator, $impersonator))->toBeFalse();

    Impersonation::begin($impersonator, 'https://example.test/start');

    expect(Impersonation::allows($impersonator, $user))->toBeFalse();

    Impersonation::end();
    $user->delete();

    expect(Impersonation::allows($impersonator, $user))->toBeFalse();
});
