<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\RotateApiToken;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    config([
        'system.api.token_prefix' => 'kit',
        'system.api.scopes' => ['orders.read', 'orders.write'],
        'system.api.token_lifetime_days' => 365,
        'system.api.rotation_overlap_days' => 7,
        'system.api.max_active_tokens' => 2,
    ]);
    $this->freezeSecond();
});

test('rotating makes a new token with the same scopes and lets the old token work for 7 more days', function (): void {
    $old = ApiToken::factory()->create(['scopes' => ['orders.read'], 'expires_at' => now()->addMonths(6)]);

    $issued = RotateApiToken::make()->execute($old);

    expect($issued->apiToken->api_client_id)->toBe($old->api_client_id)
        ->and($issued->apiToken->scopes)->toBe(['orders.read'])
        ->and($issued->apiToken->expires_at?->equalTo(now()->addDays(365)))->toBeTrue()
        ->and($old->fresh()?->expires_at?->equalTo(now()->addDays(7)))->toBeTrue();
});

test('rotating keeps the expiry of an old token that expires sooner than 7 days', function (): void {
    $old = ApiToken::factory()->create(['expires_at' => now()->addDays(2)]);

    RotateApiToken::make()->execute($old);

    expect($old->fresh()?->expires_at?->equalTo(now()->addDays(2)))->toBeTrue();
});

test('rotating a token that never expires gives a new token that never expires', function (): void {
    $old = ApiToken::factory()->neverExpires()->create();

    $issued = RotateApiToken::make()->execute($old);

    expect($issued->apiToken->fresh()?->expires_at)->toBeNull()
        ->and($old->fresh()?->expires_at?->equalTo(now()->addDays(7)))->toBeTrue();
});

test('a revoked or expired token cannot be rotated', function (string $state): void {
    $old = ApiToken::factory()->{$state}()->create();

    expect(fn (): mixed => RotateApiToken::make()->execute($old))->toThrow(LogicException::class, 'Only an active API token of an API client can be rotated.');
    expect(ApiToken::query()->count())->toBe(1);
})->with(['revoked', 'expired']);

test('rotating counts toward the maximum of active tokens, so an API client with 2 active tokens must revoke one first', function (): void {
    $apiClient = ApiClient::factory()->create();
    $old = ApiToken::factory()->for($apiClient)->create(['expires_at' => now()->addMonths(6)]);
    ApiToken::factory()->for($apiClient)->create();

    expect(fn (): mixed => RotateApiToken::make()->execute($old))->toThrow(ValidationException::class);
    expect($apiClient->tokens()->count())->toBe(2)
        ->and($old->fresh()?->expires_at?->equalTo(now()->addMonths(6)))->toBeTrue();
});
