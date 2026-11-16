<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Support\ApiTokenFormat;
use Illuminate\Support\Facades\Route;

test('a call with a valid token from an allowed IP address passes, and the API client is the user', function (): void {
    Route::middleware('api')->prefix('api/v1')->get('whoami', fn (): array => [
        'auth' => auth()->user()?->getMorphClass().':'.auth()->id(),
        'request' => request()->user()?->getMorphClass().':'.request()->user()?->getAuthIdentifier(),
        'token' => request()->user()?->currentToken()?->id,
    ]);
    $apiClient = ApiClient::factory()->allowedIpAddresses('192.0.2.0/24')->create();
    $token = createApiToken($apiClient);

    $response = $this->withToken($token)->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->getJson('/api/v1/whoami');

    $response->assertOk()->assertExactJson([
        'auth' => "api_client:$apiClient->id",
        'request' => "api_client:$apiClient->id",
        'token' => $apiClient->tokens()->sole()->id,
    ]);
});

test('a call without a valid token returns 401 with a Bearer challenge and a generic problem', function (?string $authorization): void {
    $response = $this->withHeaders(collect(['Authorization' => $authorization])->filter()->all())->getJson('/api/v1/ping');

    $response->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer')
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertExactJson([
            'type' => 'about:blank',
            'title' => 'Unauthorized',
            'status' => 401,
            'detail' => 'The request needs a valid API token.',
            'trace_id' => $response->headers->get('X-Request-Id'),
        ]);
})->with([
    'missing' => [null],
    'malformed' => ['Bearer not-a-token'],
    'unknown' => [fn (): string => 'Bearer '.ApiTokenFormat::generate()],
    'expired' => [fn (): string => 'Bearer '.createApiToken(state: ['expires_at' => now()->subMinute()])],
    'revoked' => [fn (): string => 'Bearer '.createApiToken(state: ['revoked_at' => now()])],
]);

test('a disabled API client or a call from an IP address that is not allowed returns 403 with a generic problem', function (ApiClient $apiClient): void {
    $token = createApiToken($apiClient);

    $response = $this->withToken($token)->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])->getJson('/api/v1/ping');

    $response->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertExactJson([
            'type' => 'about:blank',
            'title' => 'Forbidden',
            'status' => 403,
            'detail' => 'The API client cannot make this request.',
            'trace_id' => $response->headers->get('X-Request-Id'),
        ]);
})->with([
    'disabled' => [fn (): ApiClient => ApiClient::factory()->anyIpAddress()->disabled()->create()],
    'IP address not allowed' => [fn (): ApiClient => ApiClient::factory()->allowedIpAddresses('192.0.2.10')->create()],
    'no allowed IP address' => [fn (): ApiClient => ApiClient::factory()->allowedIpAddresses()->create()],
]);

test('a disabled API client or a call from an IP address that is not allowed returns 403 without the rate limit headers', function (ApiClient $apiClient): void {
    $token = createApiToken($apiClient);

    $response = $this->withToken($token)->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])->getJson('/api/v1/ping');

    $response->assertForbidden()
        ->assertHeaderMissing('X-RateLimit-Limit')
        ->assertHeaderMissing('X-RateLimit-Remaining');
})->with([
    'disabled' => [fn (): ApiClient => ApiClient::factory()->anyIpAddress()->disabled()->create()],
    'IP address not allowed' => [fn (): ApiClient => ApiClient::factory()->allowedIpAddresses('192.0.2.10')->create()],
]);

test('an IP address with too many failed authentications in a minute gets 429, even with a valid token', function (): void {
    config(['system.api.failed_authentication_per_minute' => 2]);
    $token = createApiToken();
    $this->getJson('/api/v1/ping')->assertUnauthorized();
    $this->getJson('/api/v1/ping')->assertUnauthorized();

    $response = $this->withToken($token)->getJson('/api/v1/ping');

    $response->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('status', 429);
});

test('successful calls do not count toward the limit of failed authentications', function (): void {
    config(['system.api.failed_authentication_per_minute' => 2]);
    $token = createApiToken();
    $this->withToken($token)->getJson('/api/v1/ping')->assertOk();
    $this->withToken($token)->getJson('/api/v1/ping')->assertOk();

    $this->withToken($token)->getJson('/api/v1/ping')->assertOk();
});

test('the limit of failed authentications counts each IP address apart', function (): void {
    config(['system.api.failed_authentication_per_minute' => 1]);
    $token = createApiToken();
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])->getJson('/api/v1/ping')->assertUnauthorized();

    $this->withToken($token)->withServerVariables(['REMOTE_ADDR' => '198.51.100.8'])->getJson('/api/v1/ping')->assertOk();
});

test('behind a trusted proxy, the allowed IP address comes from X-Forwarded-For', function (): void {
    config(['trustedproxy.proxies' => '10.0.0.0/8']);
    $token = createApiToken(ApiClient::factory()->allowedIpAddresses('192.0.2.10')->create());

    $response = $this->withToken($token)
        ->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
        ->withHeader('X-Forwarded-For', '192.0.2.10')
        ->getJson('/api/v1/ping');

    $response->assertOk();
});

test('without a trusted proxy, a spoofed X-Forwarded-For does not pass the allowed IP address check', function (): void {
    $token = createApiToken(ApiClient::factory()->allowedIpAddresses('192.0.2.10')->create());

    $response = $this->withToken($token)
        ->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->withHeader('X-Forwarded-For', '192.0.2.10')
        ->getJson('/api/v1/ping');

    $response->assertForbidden();
});

test('without a trusted proxy, a host on Forge or Vapor does not make the app trust X-Forwarded-For', function (string $host): void {
    $token = createApiToken(ApiClient::factory()->allowedIpAddresses('192.0.2.10')->create());

    $response = $this->withToken($token)
        ->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
        ->withHeader('X-Forwarded-For', '192.0.2.10')
        ->getJson("http://{$host}/api/v1/ping");

    $response->assertForbidden();

    expect(ApiCall::query()->sole()->ip_address)->toBe('198.51.100.7');
})->with(['evil.on-forge.com', 'evil.on-vapor.com']);
