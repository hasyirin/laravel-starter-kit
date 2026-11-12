<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Guards\ApiTokenGuard;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Api\Support\ApiTokenFormat;
use Illuminate\Http\Request;

function apiTokenGuard(?string $authorization): ApiTokenGuard
{
    $server = $authorization === null ? [] : ['HTTP_AUTHORIZATION' => $authorization];

    return new ApiTokenGuard(Request::create('/api/v1/ping', server: $server));
}

test('a valid token authenticates its API client with that token as the current token', function (): void {
    $apiClient = ApiClient::factory()->create();
    $token = createApiToken($apiClient);

    $guard = apiTokenGuard("Bearer $token");

    expect($guard->user()?->is($apiClient))->toBeTrue()
        ->and($guard->user()?->currentToken()?->is($guard->apiToken()))->toBeTrue()
        ->and($guard->failure())->toBeNull();
});

test('a call without a token is rejected as a missing token', function (?string $authorization): void {
    $guard = apiTokenGuard($authorization);

    expect($guard->user())->toBeNull()
        ->and($guard->failure())->toBe(ApiCallOutcome::MissingToken);
})->with([
    'no Authorization header' => [null],
    'an empty bearer token' => ['Bearer '],
    'another scheme' => ['Basic dXNlcjpwYXNz'],
]);

test('a token in the query string is not read', function (): void {
    $token = createApiToken();

    $guard = new ApiTokenGuard(Request::create('/api/v1/ping', parameters: ['api_token' => $token]));

    expect($guard->user())->toBeNull()
        ->and($guard->failure())->toBe(ApiCallOutcome::MissingToken);
});

test('a malformed or unknown token is rejected as an invalid token', function (string $token): void {
    $guard = apiTokenGuard("Bearer $token");

    expect($guard->user())->toBeNull()
        ->and($guard->failure())->toBe(ApiCallOutcome::InvalidToken)
        ->and($guard->apiToken())->toBeNull();
})->with([
    'malformed' => ['not-a-token'],
    'unknown' => [fn (): string => ApiTokenFormat::generate()],
]);

test('a token of a deleted API client is rejected as an invalid token', function (): void {
    $apiClient = ApiClient::factory()->create();
    $token = createApiToken($apiClient);
    $apiClient->delete();

    $guard = apiTokenGuard("Bearer $token");

    expect($guard->user())->toBeNull()
        ->and($guard->failure())->toBe(ApiCallOutcome::InvalidToken);
});

test('an expired or revoked token is rejected with its reason, and the guard keeps the token', function (array $state, ApiCallOutcome $outcome): void {
    $token = createApiToken(state: $state);

    $guard = apiTokenGuard("Bearer $token");

    expect($guard->user())->toBeNull()
        ->and($guard->failure())->toBe($outcome)
        ->and($guard->apiToken())->toBeInstanceOf(ApiToken::class);
})->with([
    'expired' => [fn (): array => ['expires_at' => now()->subMinute()], ApiCallOutcome::ExpiredToken],
    'revoked' => [fn (): array => ['revoked_at' => now()], ApiCallOutcome::RevokedToken],
    'revoked and expired' => [fn (): array => ['revoked_at' => now(), 'expires_at' => now()->subMinute()], ApiCallOutcome::RevokedToken],
]);

test('a successful authentication records the use of the token', function (): void {
    $token = createApiToken();

    apiTokenGuard("Bearer $token")->user();

    expect(ApiToken::query()->sole()->last_used_at)->not->toBeNull();
});

test('a rejected token does not record a use', function (): void {
    $token = createApiToken(state: ['revoked_at' => now()]);

    apiTokenGuard("Bearer $token")->user();

    expect(ApiToken::query()->sole()->last_used_at)->toBeNull();
});

test('a new request clears the result of the last request', function (): void {
    $guard = apiTokenGuard('Bearer '.createApiToken());
    $guard->user();

    $guard->setRequest(Request::create('/api/v1/ping'));

    expect($guard->user())->toBeNull()
        ->and($guard->apiToken())->toBeNull()
        ->and($guard->failure())->toBe(ApiCallOutcome::MissingToken);
});
