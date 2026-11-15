<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\IssueApiToken;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Api\Support\ApiTokenFormat;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    config([
        'system.api.token_prefix' => 'kit',
        'system.api.scopes' => ['orders.read', 'orders.write'],
        'system.api.token_lifetime_days' => 365,
        'system.api.max_active_tokens' => 2,
    ]);
});

test('issuing gives the plain token one time and stores only its hash and last 4 characters', function (): void {
    $this->freezeSecond();
    $apiClient = ApiClient::factory()->create();

    $issued = IssueApiToken::make()->execute($apiClient, ['orders.read']);

    $stored = ApiToken::query()->sole();
    expect(ApiTokenFormat::valid($issued->plainToken))->toBeTrue()
        ->and($issued->apiToken->is($stored))->toBeTrue()
        ->and($stored->api_client_id)->toBe($apiClient->id)
        ->and($stored->token_hash)->toBe(hash('sha256', $issued->plainToken))
        ->and($stored->last_four)->toBe(substr($issued->plainToken, -4))
        ->and($stored->scopes)->toBe(['orders.read'])
        ->and($stored->expires_at?->equalTo(now()->addDays(365)))->toBeTrue()
        ->and(collect($stored->getAttributes())->contains($issued->plainToken))->toBeFalse();
});

test('a token that never expires has no expiry time', function (): void {
    $issued = IssueApiToken::make()->execute(ApiClient::factory()->create(), [], neverExpires: true);

    expect($issued->apiToken->fresh()?->expires_at)->toBeNull();
});

test('issuing rejects a scope that the app does not declare', function (): void {
    $apiClient = ApiClient::factory()->create();

    expect(fn (): mixed => IssueApiToken::make()->execute($apiClient, ['orders.read', 'orders.delete']))->toThrow(LogicException::class, 'The scope [orders.delete] is not in system.api.scopes.');
    expect(ApiToken::query()->exists())->toBeFalse();
});

test('an API client can hold at most the maximum of active tokens', function (): void {
    $apiClient = ApiClient::factory()->create();
    ApiToken::factory()->for($apiClient)->count(2)->create();

    expect(fn (): mixed => IssueApiToken::make()->execute($apiClient, []))->toThrow(ValidationException::class, 'An API client can have at most 2 active API tokens. Revoke one first.');
    expect($apiClient->tokens()->count())->toBe(2);
});

test('revoked and expired tokens do not count toward the maximum', function (): void {
    $apiClient = ApiClient::factory()->create();
    ApiToken::factory()->for($apiClient)->create();
    ApiToken::factory()->for($apiClient)->revoked()->create();
    ApiToken::factory()->for($apiClient)->expired()->create();

    IssueApiToken::make()->execute($apiClient, []);

    expect($apiClient->tokens()->active()->count())->toBe(2);
});
