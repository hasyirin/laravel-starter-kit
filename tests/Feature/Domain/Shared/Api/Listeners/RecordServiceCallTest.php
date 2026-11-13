<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\User\Models\User;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    config(['system.api.services' => [
        'billing' => ['base_url' => 'https://billing.test', 'timeout' => 5, 'connect_timeout' => 2, 'retry' => 1, 'log_bodies' => false],
    ]]);
});

test('a call to a service makes one outbound row with the service, the method, the URL, the status, the outcome, the sizes and the trace ID', function (int $status, ApiCallOutcome $outcome): void {
    context()->add('trace_id', 'trace-123');
    Http::fake(['billing.test/*' => Http::response('{"id":7}', $status)]);

    Http::service('billing')->post('/v1/bills', ['amount' => 10]);

    expect(ApiCall::query()->sole()->only('direction', 'service', 'method', 'url', 'status', 'outcome', 'trace_id', 'request_size', 'response_size', 'error'))->toBe([
        'direction' => ApiCallDirection::Outbound,
        'service' => 'billing',
        'method' => 'POST',
        'url' => 'https://billing.test/v1/bills',
        'status' => $status,
        'outcome' => $outcome,
        'trace_id' => 'trace-123',
        'request_size' => strlen('{"amount":10}'),
        'response_size' => strlen('{"id":7}'),
        'error' => null,
    ]);
})->with([
    'ok' => [201, ApiCallOutcome::Ok],
    'client error' => [404, ApiCallOutcome::ClientError],
    'validation failed' => [422, ApiCallOutcome::ValidationFailed],
    'rate limited' => [429, ApiCallOutcome::RateLimited],
    'server error' => [503, ApiCallOutcome::ServerError],
]);

test('a failed connection makes a row without a status, with the connection failed outcome and the error text', function (): void {
    Http::fake(['billing.test/*' => Http::failedConnection('cURL error 7: Failed to connect')]);

    expect(fn () => Http::service('billing')->get('/v1/status'))->toThrow(ConnectionException::class);

    expect(ApiCall::query()->sole()->only('service', 'url', 'status', 'outcome', 'response_size', 'error'))->toBe([
        'service' => 'billing',
        'url' => 'https://billing.test/v1/status',
        'status' => null,
        'outcome' => ApiCallOutcome::ConnectionFailed,
        'response_size' => null,
        'error' => 'cURL error 7: Failed to connect',
    ]);
});

test('the row keeps how long the call took', function (): void {
    Http::fake(function (): PromiseInterface {
        Sleep::usleep(30_000);

        return Http::response();
    });

    Http::service('billing')->get('/v1/status');

    expect(ApiCall::query()->sole()->duration_ms)->toBeGreaterThanOrEqual(30);
});

test('each attempt of a retried call makes its own row', function (): void {
    config(['system.api.services.billing.retry' => 3]);
    Http::fake(['billing.test/*' => Http::sequence()->pushFailedConnection()->push('', 503)->push('{}', 200)]);

    $response = Http::service('billing')->get('/v1/status');

    expect($response->status())->toBe(200)
        ->and(ApiCall::query()->oldest('id')->pluck('outcome')->all())->toBe([ApiCallOutcome::ConnectionFailed, ApiCallOutcome::ServerError, ApiCallOutcome::Ok]);
});

test('a retried call that fails at each attempt returns the last response and does not throw', function (): void {
    config(['system.api.services.billing.retry' => 2]);
    Http::fake(['billing.test/*' => Http::response('', 500)]);

    $response = Http::service('billing')->get('/v1/status');

    expect($response->status())->toBe(500)
        ->and(ApiCall::query()->count())->toBe(2);
});

test('a call that does not go through the service entry point makes no row', function (): void {
    Http::fake();

    Http::get('https://billing.test/v1/status');
    Http::baseUrl('https://billing.test')->withAttributes(['source' => 'app'])->get('/v1/status');

    expect(ApiCall::query()->exists())->toBeFalse();
});

test('the row keeps no bodies when the service does not log bodies', function (): void {
    Http::fake(['billing.test/*' => Http::response('{"id":7}')]);

    Http::service('billing')->post('/v1/bills', ['amount' => 10]);

    expect(ApiCall::query()->sole()->only('request_body', 'response_body'))->toBe(['request_body' => null, 'response_body' => null]);
});

test('a service that logs bodies keeps both bodies with the listed keys masked', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    Http::fake(['billing.test/*' => Http::response(['id' => 7, 'access_token' => 'issued-secret'])]);

    Http::service('billing')->post('/v1/bills', ['amount' => 10, 'password' => 'hunter2']);

    expect(ApiCall::query()->sole()->only('request_body', 'response_body'))->toBe([
        'request_body' => '{"amount":10,"password":"[hidden]"}',
        'response_body' => '{"id":7,"access_token":"[hidden]"}',
    ]);
});

test('a service that logs bodies stores a binary response body as valid UTF-8 without NUL bytes', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    Http::fake(['billing.test/*' => Http::response("\x89PNG\r\n\x1a\n\0\0\0\rIHDR\xff")]);

    Http::service('billing')->get('/v1/logo');

    $responseBody = (string) ApiCall::query()->sole()->response_body;
    expect(mb_check_encoding($responseBody, 'UTF-8'))->toBeTrue()
        ->and($responseBody)->not->toContain("\0");
});

test('a service that logs bodies masks the listed keys of a form body too', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    Http::fake();

    Http::service('billing')->asForm()->post('/oauth/token', ['grant_type' => 'client_credentials', 'client_secret' => 's3cret']);

    expect(ApiCall::query()->sole()->request_body)->toBe('{"grant_type":"client_credentials","client_secret":"[hidden]"}');
});

test('the row never keeps the headers, the credentials in the URL or the query string', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    Http::fake(['billing.test/*' => Http::failedConnection('cURL error 28: Timed out for https://user:pass@billing.test/v1/status?api_key=leaked')]);

    expect(fn () => Http::service('billing')
        ->withToken('bearer-secret')
        ->withHeader('X-Api-Key', 'header-secret')
        ->get('https://user:pass@billing.test/v1/status?api_key=leaked'))->toThrow(ConnectionException::class);

    $apiCall = ApiCall::query()->sole();
    expect($apiCall->url)->toBe('https://billing.test/v1/status')
        ->and($apiCall->error)->toBe('cURL error 28: Timed out for https://billing.test/v1/status')
        ->and(json_encode($apiCall->getAttributes()))->not->toContain('bearer-secret', 'header-secret', 'leaked', 'pass');
});

test('the row carries the API client that is linked to the service, and its name', function (): void {
    $apiClient = ApiClient::factory()->create(['name' => 'Billing', 'service' => 'billing']);
    ApiClient::factory()->create(['service' => 'payroll']);
    Http::fake();

    Http::service('billing')->get('/v1/status');

    expect(ApiCall::query()->sole()->only('api_client_id', 'api_client_name'))->toBe(['api_client_id' => $apiClient->id, 'api_client_name' => 'Billing']);
});

test('the row carries no API client when none is linked to the service', function (): void {
    ApiClient::factory()->create(['service' => 'payroll']);
    Http::fake();

    Http::service('billing')->get('/v1/status');

    expect(ApiCall::query()->sole()->api_client_id)->toBeNull();
});

test('a call about a record keeps that record on the row', function (): void {
    $user = User::factory()->create();
    Http::fake();

    Http::service('billing')->about($user)->get('/v1/status');

    expect(ApiCall::query()->sole()->resource?->is($user))->toBeTrue();
});
