<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Api\Support\ApiTokenFormat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('api')->prefix('api/v1')->name('api.v1.')->group(function (): void {
        Route::post('secrets', fn (): array => ['client_secret' => 'returned-secret', 'saved' => true])->middleware('log-bodies')->name('secrets');
        Route::post('notes', fn (): array => request()->validate(['title' => ['required', 'string']]))->name('notes');
        Route::get('failure', fn (): never => throw new RuntimeException('Database password is hunter2'))->name('failure');
    });
});

test('a successful call writes one inbound row with the API client, the token and the request', function (): void {
    $apiClient = ApiClient::factory()->anyIpAddress()->create(['name' => 'Billing']);
    $token = createApiToken($apiClient);

    $response = $this->withToken($token)
        ->withHeader('User-Agent', 'billing-sync/1.0')
        ->withHeader('X-Request-Id', 'trace-123')
        ->getJson('/api/v1/ping?page=2&token=leaked');

    $call = ApiCall::query()->sole();
    expect($call)
        ->direction->toBe(ApiCallDirection::Inbound)
        ->api_client_id->toBe($apiClient->id)
        ->api_client_name->toBe('Billing')
        ->api_token_id->toBe(ApiToken::query()->sole()->id)
        ->method->toBe('GET')
        ->url->toBe('/api/v1/ping')
        ->route_name->toBe('api.v1.ping')
        ->status->toBe(200)
        ->outcome->toBe(ApiCallOutcome::Ok)
        ->ip_address->toBe('127.0.0.1')
        ->user_agent->toBe('billing-sync/1.0')
        ->trace_id->toBe('trace-123')
        ->request_size->toBe(strlen('[]'))
        ->response_size->toBe(strlen((string) $response->getContent()))
        ->request_body->toBeNull()
        ->response_body->toBeNull()
        ->and($call->duration_ms)->toBeGreaterThanOrEqual(0);
});

test('each rejected call writes a row with the reason as its outcome', function (Closure $call, ApiCallOutcome $outcome, int $status): void {
    $call->call($this);

    expect(ApiCall::query()->sole())
        ->outcome->toBe($outcome)
        ->status->toBe($status);
})->with([
    'missing token' => [fn () => $this->getJson('/api/v1/ping'), ApiCallOutcome::MissingToken, 401],
    'invalid token' => [fn () => $this->withToken(ApiTokenFormat::generate())->getJson('/api/v1/ping'), ApiCallOutcome::InvalidToken, 401],
    'expired token' => [fn () => $this->withToken(createApiToken(state: ['expires_at' => now()->subMinute()]))->getJson('/api/v1/ping'), ApiCallOutcome::ExpiredToken, 401],
    'revoked token' => [fn () => $this->withToken(createApiToken(state: ['revoked_at' => now()]))->getJson('/api/v1/ping'), ApiCallOutcome::RevokedToken, 401],
    'disabled API client' => [fn () => $this->withToken(createApiToken(ApiClient::factory()->anyIpAddress()->disabled()->create()))->getJson('/api/v1/ping'), ApiCallOutcome::ClientDisabled, 403],
    'IP address not allowed' => [fn () => $this->withToken(createApiToken(ApiClient::factory()->create()))->getJson('/api/v1/ping'), ApiCallOutcome::IpNotAllowed, 403],
    'validation failed' => [fn () => $this->withToken(createApiToken())->postJson('/api/v1/notes'), ApiCallOutcome::ValidationFailed, 422],
    'unknown path' => [fn () => $this->withToken(createApiToken())->getJson('/api/v1/missing'), ApiCallOutcome::ClientError, 404],
    'wrong method' => [fn () => $this->deleteJson('/api/v1/ping'), ApiCallOutcome::ClientError, 405],
    'unhandled exception' => [fn () => $this->withToken(createApiToken())->getJson('/api/v1/failure'), ApiCallOutcome::ServerError, 500],
]);

test('a call that goes over the rate limit of the API client writes a rate limited row', function (): void {
    $token = createApiToken(ApiClient::factory()->anyIpAddress()->create(['rate_limit_per_minute' => 1]));
    $this->withToken($token)->getJson('/api/v1/ping')->assertOk();

    $this->withToken($token)->getJson('/api/v1/ping')->assertTooManyRequests();

    expect(ApiCall::query()->latest('id')->first()?->outcome)->toBe(ApiCallOutcome::RateLimited);
});

test('a call with an expired or revoked token keeps the token and the API client in its row', function (array $state): void {
    $apiClient = ApiClient::factory()->anyIpAddress()->create();
    $token = createApiToken($apiClient, $state);

    $this->withToken($token)->getJson('/api/v1/ping');

    expect(ApiCall::query()->sole())
        ->api_client_id->toBe($apiClient->id)
        ->api_token_id->toBe(ApiToken::query()->sole()->id);
})->with([
    'expired' => [fn (): array => ['expires_at' => now()->subMinute()]],
    'revoked' => [fn (): array => ['revoked_at' => now()]],
]);

test('a call with a token of a deleted API client is rejected and keeps the API client and its name in its row', function (): void {
    $apiClient = ApiClient::factory()->anyIpAddress()->create(['name' => 'Billing']);
    $token = createApiToken($apiClient);
    $apiClient->delete();

    $this->withToken($token)->getJson('/api/v1/ping')->assertUnauthorized();

    expect(ApiCall::query()->sole())
        ->outcome->toBe(ApiCallOutcome::InvalidToken)
        ->api_client_id->toBe($apiClient->id)
        ->api_client_name->toBe('Billing')
        ->api_token_id->toBe(ApiToken::query()->sole()->id);
});

test('a call with an unknown token writes a row without an API client', function (): void {
    $this->withToken(ApiTokenFormat::generate())->getJson('/api/v1/ping');

    expect(ApiCall::query()->sole())
        ->api_client_id->toBeNull()
        ->api_token_id->toBeNull();
});

test('a user agent with bytes that are not UTF-8 or NUL bytes is stored as valid UTF-8 without NUL bytes', function (): void {
    $this->withHeader('User-Agent', "bad\xff\0agent")->getJson('/api/v1/ping');

    expect(ApiCall::query()->sole()->user_agent)->toBe('bad?agent');
});

test('a route that opts in stores the masked bodies, and the Authorization header is never stored', function (): void {
    $token = createApiToken();

    $this->withToken($token)->postJson('/api/v1/secrets', ['password' => 'sent-secret', 'title' => 'Hello']);

    $call = ApiCall::query()->sole();
    expect($call->request_body)->toBe('{"password":"[hidden]","title":"Hello"}')
        ->and($call->response_body)->toBe('{"client_secret":"[hidden]","saved":true}')
        ->and(json_encode($call->getAttributes()))->not->toContain($token);
});

test('a route that does not opt in stores no bodies', function (): void {
    $token = createApiToken();

    $this->withToken($token)->postJson('/api/v1/notes', ['title' => 'Hello'])->assertOk();

    expect(ApiCall::query()->sole())
        ->request_body->toBeNull()
        ->response_body->toBeNull()
        ->request_size->toBe(strlen('{"title":"Hello"}'));
});

test('a multipart call stores the request size from the Content-Length header', function (): void {
    $token = createApiToken();

    $this->withToken($token)
        ->withHeader('Content-Length', '512000')
        ->post('/api/v1/notes', ['title' => 'Hello', 'file' => UploadedFile::fake()->create('report.pdf', 500)]);

    expect(ApiCall::query()->sole()->request_size)->toBe(512000);
});

test('a call without a numeric Content-Length header stores no request size', function (array $headers): void {
    $this->withHeaders($headers)->get('/api/v1/ping');

    expect(ApiCall::query()->sole()->request_size)->toBeNull();
})->with([
    'no header' => [[]],
    'not a number' => [['Content-Length' => 'abc']],
]);

test('a call to a web route writes no row', function (): void {
    $this->get('/')->assertOk();

    $this->assertDatabaseEmpty(ApiCall::class);
});
