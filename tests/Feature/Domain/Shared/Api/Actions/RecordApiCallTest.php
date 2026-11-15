<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\RecordApiCall;
use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Models\ApiToken;
use App\Domain\Shared\Organization\Models\Organization;

test('recording an inbound call writes one row with the API client, its name, the token and the trace ID of the request', function (): void {
    context()->add('trace_id', 'trace-123');
    $apiClient = ApiClient::factory()->create(['name' => 'Billing']);
    $apiToken = ApiToken::factory()->for($apiClient)->create();

    $apiCall = RecordApiCall::make()->execute(
        direction: ApiCallDirection::Inbound,
        method: 'POST',
        url: '/api/v1/orders',
        outcome: ApiCallOutcome::Ok,
        durationMs: 42,
        status: 201,
        apiClient: $apiClient,
        apiToken: $apiToken,
        routeName: 'api.v1.orders.store',
        ipAddress: '192.0.2.10',
        userAgent: 'Billing/1.0',
        requestSize: 120,
        responseSize: 64,
    );

    expect($apiCall->fresh()?->only('direction', 'api_client_id', 'api_client_name', 'api_token_id', 'method', 'url', 'route_name', 'status', 'outcome', 'duration_ms', 'ip_address', 'user_agent', 'trace_id', 'request_size', 'response_size', 'request_body', 'response_body'))->toBe([
        'direction' => ApiCallDirection::Inbound,
        'api_client_id' => $apiClient->id,
        'api_client_name' => 'Billing',
        'api_token_id' => $apiToken->id,
        'method' => 'POST',
        'url' => '/api/v1/orders',
        'route_name' => 'api.v1.orders.store',
        'status' => 201,
        'outcome' => ApiCallOutcome::Ok,
        'duration_ms' => 42,
        'ip_address' => '192.0.2.10',
        'user_agent' => 'Billing/1.0',
        'trace_id' => 'trace-123',
        'request_size' => 120,
        'response_size' => 64,
        'request_body' => null,
        'response_body' => null,
    ]);
});

test('a call with an unknown token is recorded without an API client', function (): void {
    $apiCall = RecordApiCall::make()->execute(
        direction: ApiCallDirection::Inbound,
        method: 'GET',
        url: '/api/v1/ping',
        outcome: ApiCallOutcome::InvalidToken,
        durationMs: 3,
        status: 401,
    );

    expect($apiCall->fresh())->api_client_id->toBeNull()->api_client_name->toBeNull()->outcome->toBe(ApiCallOutcome::InvalidToken);
});

test('an outbound call that failed to connect is recorded with the service, the related record and the error, and no status', function (): void {
    $organization = Organization::factory()->create();

    $apiCall = RecordApiCall::make()->execute(
        direction: ApiCallDirection::Outbound,
        method: 'GET',
        url: 'https://billing.example.com/invoices',
        outcome: ApiCallOutcome::ConnectionFailed,
        durationMs: 5000,
        service: 'billing',
        resource: $organization,
        error: 'cURL error 28: Operation timed out.',
    );

    expect($apiCall->fresh())
        ->service->toBe('billing')
        ->status->toBeNull()
        ->error->toBe('cURL error 28: Operation timed out.')
        ->and($apiCall->fresh()?->resource?->is($organization))->toBeTrue();
});

test('recorded bodies are masked and capped', function (): void {
    config(['system.api_log.masked_keys' => ['password'], 'system.api_log.body_limit' => 100]);

    $apiCall = RecordApiCall::make()->execute(
        direction: ApiCallDirection::Inbound,
        method: 'POST',
        url: '/api/v1/users',
        outcome: ApiCallOutcome::Ok,
        durationMs: 10,
        status: 200,
        requestBody: '{"email":"ali@example.com","password":"hunter2"}',
        responseBody: str_repeat('x', 150),
    );

    expect($apiCall->fresh())
        ->request_body->toBe('{"email":"ali@example.com","password":"[hidden]"}')
        ->response_body->toBe(str_repeat('x', 100));
});

test('a JSON body with a byte that is not UTF-8 is stored masked', function (): void {
    config(['system.api_log.masked_keys' => ['password']]);

    $apiCall = RecordApiCall::make()->execute(
        direction: ApiCallDirection::Inbound,
        method: 'POST',
        url: '/api/v1/users',
        outcome: ApiCallOutcome::Ok,
        durationMs: 10,
        status: 200,
        requestBody: "{\"password\":\"s3cret\",\"note\":\"\xff\"}",
    );

    expect($apiCall->fresh()?->request_body)->toBe('{"password":"[hidden]","note":"?"}');
});
