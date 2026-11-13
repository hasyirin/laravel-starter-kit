<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Connectors\ServiceConnector;
use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Api\Models\ApiClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Saloon\Config;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\StrayRequestException;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Http\Senders\GuzzleSender;
use Saloon\Laravel\Facades\Saloon;
use Tests\Fixtures\Connectors\BillingConnector;
use Tests\Fixtures\Requests\CreateBill;
use Tests\Fixtures\Requests\GetStatus;
use Tests\Fixtures\Requests\RequestToken;
use Tests\Fixtures\Requests\UploadLogo;

beforeEach(function (): void {
    config(['system.api.services' => [
        'billing' => ['base_url' => 'https://billing.test', 'timeout' => 5, 'connect_timeout' => 2, 'log_bodies' => false],
    ]]);
});

afterEach(function (): void {
    Config::sleepUsing(null);
});

function failedServiceConnection(string $message): MockResponse
{
    return MockResponse::make()->throw(fn (PendingRequest $pendingRequest): FatalRequestException => new FatalRequestException(
        new ConnectException($message, $pendingRequest->createPsrRequest()),
        $pendingRequest,
    ));
}

test('a connector sends to the base URL of the service with its timeouts and the trace ID, and adds no credentials', function (): void {
    context()->add('trace_id', 'trace-123');
    Saloon::fake([MockResponse::make()]);

    $response = new BillingConnector()->send(new GetStatus);

    $psrRequest = $response->getPsrRequest();
    expect((string) $psrRequest->getUri())->toBe('https://billing.test/v1/status')
        ->and($psrRequest->getHeaderLine('X-Request-Id'))->toBe('trace-123')
        ->and($psrRequest->hasHeader('Authorization'))->toBeFalse()
        ->and($response->getPendingRequest()->config()->all())->toMatchArray(['timeout' => 5.0, 'connect_timeout' => 2.0]);
});

test('a service name that system.api.services does not list is refused', function (): void {
    $connector = new class extends ServiceConnector
    {
        public function service(): string
        {
            return 'acme';
        }
    };

    expect(fn (): Response => $connector->send(new GetStatus))->toThrow(LogicException::class, 'The service [acme] is not in system.api.services.');
});

test('a call to a service in a test without a fake fails before it is sent', function (): void {
    config(['system.api.services.billing.base_url' => 'https://billing.invalid']);

    expect(fn (): Response => new BillingConnector()->send(new GetStatus))->toThrow(StrayRequestException::class);
});

test('a call to a service makes one outbound row with the service, the method, the URL, the status, the outcome, the sizes and the trace ID', function (int $status, ApiCallOutcome $outcome): void {
    context()->add('trace_id', 'trace-123');
    Saloon::fake([MockResponse::make('{"id":7}', $status)]);

    new BillingConnector()->send(new CreateBill(['amount' => 10]));

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
    Saloon::fake([failedServiceConnection('cURL error 7: Failed to connect')]);

    expect(fn (): Response => new BillingConnector()->send(new GetStatus))->toThrow(FatalRequestException::class);

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
    Saloon::fake([function (): MockResponse {
        Sleep::usleep(30_000);

        return MockResponse::make();
    }]);

    new BillingConnector()->send(new GetStatus);

    expect(ApiCall::query()->sole()->duration_ms)->toBeGreaterThanOrEqual(30);
});

test('each attempt of a retried call makes its own row', function (): void {
    config(['system.api.services.billing.tries' => 3]);
    Saloon::fake([failedServiceConnection('cURL error 7: Failed to connect'), MockResponse::make('', 503), MockResponse::make('{}')]);

    $response = new BillingConnector()->send(new GetStatus);

    expect($response->status())->toBe(200)
        ->and(ApiCall::query()->oldest('id')->pluck('outcome')->all())->toBe([ApiCallOutcome::ConnectionFailed, ApiCallOutcome::ServerError, ApiCallOutcome::Ok]);
});

test('the duration of each row is the time of its own attempt, without the wait before it', function (): void {
    config(['system.api.services.billing.tries' => 2, 'system.api.services.billing.retry_interval' => 50]);
    Saloon::fake([
        function (): MockResponse {
            Sleep::usleep(40_000);

            return MockResponse::make('', 503);
        },
        MockResponse::make(),
    ]);

    new BillingConnector()->send(new GetStatus);

    [$first, $second] = ApiCall::query()->oldest('id')->pluck('duration_ms')->all();
    expect($first)->toBeGreaterThanOrEqual(40)
        ->and($second)->toBeLessThan(40);
});

test('a retried call that fails at each attempt returns the last response and does not throw', function (): void {
    config(['system.api.services.billing.tries' => 2]);
    Saloon::fake([MockResponse::make('', 500), MockResponse::make('', 500)]);

    $response = new BillingConnector()->send(new GetStatus);

    expect($response->status())->toBe(500)
        ->and(ApiCall::query()->count())->toBe(2);
});

test('the retry settings of the service set the waits between the attempts', function (bool $exponentialBackoff, array $waits): void {
    config(['system.api.services.billing' => [
        ...config('system.api.services.billing'),
        'tries' => 3,
        'retry_interval' => 100,
        'exponential_backoff' => $exponentialBackoff,
    ]]);
    $sleeps = collect();
    Config::sleepUsing(fn (int $microseconds) => $sleeps->push($microseconds));
    Saloon::fake([MockResponse::make('', 503), MockResponse::make('', 503), MockResponse::make('', 503)]);

    new BillingConnector()->send(new GetStatus);

    expect($sleeps->all())->toBe($waits);
})->with([
    'fixed' => [false, [100_000, 100_000]],
    'exponential' => [true, [100_000, 200_000]],
]);

test('a call that does not go through a service connector makes no row', function (): void {
    Http::fake();
    Saloon::fake([MockResponse::make()]);
    $connector = new class extends Connector
    {
        public function resolveBaseUrl(): string
        {
            return 'https://billing.test';
        }
    };

    Http::get('https://billing.test/v1/status');
    $connector->send(new GetStatus);

    expect(ApiCall::query()->exists())->toBeFalse();
});

test('Http::fake() does not catch a call through a service connector', function (): void {
    Http::fake(['*' => Http::response('faked by Http::fake')]);
    Saloon::fake([MockResponse::make('mocked by Saloon')]);

    $response = new BillingConnector()->send(new GetStatus);

    expect($response->body())->toBe('mocked by Saloon');
    Http::assertNothingSent();
});

test('the row keeps no bodies when the service does not log bodies', function (): void {
    Saloon::fake([MockResponse::make('{"id":7}')]);

    new BillingConnector()->send(new CreateBill(['amount' => 10]));

    expect(ApiCall::query()->sole()->only('request_body', 'response_body'))->toBe(['request_body' => null, 'response_body' => null]);
});

test('a service that logs bodies keeps both bodies with the listed keys masked', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    Saloon::fake([MockResponse::make(['id' => 7, 'access_token' => 'issued-secret'])]);

    new BillingConnector()->send(new CreateBill(['amount' => 10, 'password' => 'hunter2']));

    expect(ApiCall::query()->sole()->only('request_body', 'response_body'))->toBe([
        'request_body' => '{"amount":10,"password":"[hidden]"}',
        'response_body' => '{"id":7,"access_token":"[hidden]"}',
    ]);
});

test('a service that logs bodies stores a binary response body as valid UTF-8 without NUL bytes', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    Saloon::fake([MockResponse::make("\x89PNG\r\n\x1a\n\0\0\0\rIHDR\xff")]);

    new BillingConnector()->send(new GetStatus);

    $responseBody = (string) ApiCall::query()->sole()->response_body;
    expect(mb_check_encoding($responseBody, 'UTF-8'))->toBeTrue()
        ->and($responseBody)->not->toContain("\0");
});

test('a service that logs bodies masks the listed keys of a form body too', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    Saloon::fake([MockResponse::make()]);

    new BillingConnector()->send(new RequestToken('s3cret'));

    expect(ApiCall::query()->sole()->request_body)->toBe('{"grant_type":"client_credentials","client_secret":"[hidden]"}');
});

test('a service that logs bodies keeps no multipart body, because it can hold files', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    Saloon::fake([MockResponse::make()]);

    new BillingConnector()->send(new UploadLogo);

    expect(ApiCall::query()->sole()->request_body)->toBeNull();
});

test('the row never keeps the headers, the credentials in the URL or the query string', function (): void {
    config(['system.api.services.billing.base_url' => 'https://user:pass@billing.test', 'system.api.services.billing.log_bodies' => true]);
    Saloon::fake([failedServiceConnection('cURL error 28: Timed out for https://user:pass@billing.test/v1/status?api_key=leaked')]);
    $connector = new BillingConnector()->authenticate(new TokenAuthenticator('bearer-secret'));
    $request = new GetStatus;
    $request->headers()->add('X-Api-Key', 'header-secret');
    $request->query()->add('api_key', 'leaked');

    expect(fn (): Response => $connector->send($request))->toThrow(FatalRequestException::class);

    $apiCall = ApiCall::query()->sole();
    expect($apiCall->url)->toBe('https://billing.test/v1/status')
        ->and($apiCall->error)->toBe('cURL error 28: Timed out for https://billing.test/v1/status')
        ->and(json_encode($apiCall->getAttributes()))->not->toContain('bearer-secret', 'header-secret', 'leaked', 'pass');
});

test('a streamed response reaches the caller in full and still makes a row without its body', function (): void {
    config(['system.api.services.billing.log_bodies' => true]);
    $connector = new BillingConnector;
    $sender = $connector->sender();
    assert($sender instanceof GuzzleSender);
    $sender->getHandlerStack()->setHandler(new MockHandler([
        new PsrResponse(200, [], new NoSeekStream(Utils::streamFor('{"id":7}'))),
    ]));

    Config::allowStrayRequests();

    try {
        $response = $connector->send(new GetStatus);
    } finally {
        Config::preventStrayRequests();
    }

    expect($response->body())->toBe('{"id":7}')
        ->and(ApiCall::query()->sole()->only('status', 'response_size', 'response_body'))->toBe([
            'status' => 200,
            'response_size' => strlen('{"id":7}'),
            'response_body' => null,
        ]);
});

test('a response middleware of the connector that throws still leaves a row', function (): void {
    Saloon::fake([MockResponse::make('', 500)]);
    $connector = new class extends ServiceConnector
    {
        public function service(): string
        {
            return 'billing';
        }

        public function boot(PendingRequest $pendingRequest): void
        {
            $pendingRequest->middleware()->onResponse(fn (Response $response): Response => $response->throw());
        }
    };

    $connector->send(new GetStatus);

    expect(ApiCall::query()->sole()->outcome)->toBe(ApiCallOutcome::ServerError);
});

test('the row carries the API client that is linked to the service, and its name', function (): void {
    $apiClient = ApiClient::factory()->create(['name' => 'Billing', 'service' => 'billing']);
    ApiClient::factory()->create(['service' => 'payroll']);
    Saloon::fake([MockResponse::make()]);

    new BillingConnector()->send(new GetStatus);

    expect(ApiCall::query()->sole()->only('api_client_id', 'api_client_name'))->toBe(['api_client_id' => $apiClient->id, 'api_client_name' => 'Billing']);
});

test('the row carries no API client when none is linked to the service', function (): void {
    ApiClient::factory()->create(['service' => 'payroll']);
    Saloon::fake([MockResponse::make()]);

    new BillingConnector()->send(new GetStatus);

    expect(ApiCall::query()->sole()->api_client_id)->toBeNull();
});
