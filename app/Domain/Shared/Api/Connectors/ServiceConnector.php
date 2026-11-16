<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Connectors;

use App\Domain\Shared\Api\Actions\RecordApiCall;
use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Api\Requests\ServiceRequest;
use LogicException;
use Psr\Http\Message\RequestInterface;
use Saloon\Contracts\Body\BodyRepository;
use Saloon\Enums\PipeOrder;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Repositories\Body\FormBodyRepository;
use Saloon\Repositories\Body\MultipartBodyRepository;
use Saloon\Repositories\Body\StreamBodyRepository;
use Saloon\Traits\Plugins\HasTimeout;

abstract class ServiceConnector extends Connector
{
    use HasTimeout;

    abstract public function service(): string;

    public function resolveBaseUrl(): string
    {
        return config()->string("{$this->configKey()}.base_url");
    }

    public function getConnectTimeout(): float
    {
        return config()->integer("{$this->configKey()}.connect_timeout", 10);
    }

    public function getRequestTimeout(): float
    {
        return config()->integer("{$this->configKey()}.timeout", 30);
    }

    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        $key = $this->configKey();

        $this->tries = config()->integer("{$key}.tries", 1);
        $this->retryInterval = config()->integer("{$key}.retry_interval", 0);
        $this->useExponentialBackoff = config()->boolean("{$key}.exponential_backoff", false);
        $this->throwOnMaxTries = false;

        return parent::send($request, $mockClient, $handleRetry);
    }

    public function createPendingRequest(Request $request, ?MockClient $mockClient = null): PendingRequest
    {
        $startedAt = microtime(true);
        $pendingRequest = parent::createPendingRequest($request, $mockClient);
        $traceId = context()->get('trace_id');

        if (is_string($traceId)) {
            $pendingRequest->headers()->add('X-Request-Id', $traceId);
        }

        $pendingRequest->middleware()
            ->onResponse(function (Response $response) use ($pendingRequest, $startedAt): void {
                $this->record($pendingRequest, $startedAt, $response->getPsrRequest(), $response);
            }, order: PipeOrder::FIRST)
            ->onFatalException(function (FatalRequestException $exception) use ($pendingRequest, $startedAt): void {
                $this->record($pendingRequest, $startedAt, $pendingRequest->createPsrRequest(), error: $exception->getMessage());
            }, order: PipeOrder::FIRST);

        return $pendingRequest;
    }

    private function configKey(): string
    {
        $service = $this->service();
        $key = "system.api.services.{$service}";

        throw_unless(is_array(config($key)), LogicException::class, "The service [{$service}] is not in system.api.services.");

        return $key;
    }

    private function record(PendingRequest $pendingRequest, float $startedAt, RequestInterface $psrRequest, ?Response $response = null, ?string $error = null): void
    {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $logsBodies = config()->boolean("{$this->configKey()}.log_bodies", false);
        $request = $pendingRequest->getRequest();
        $url = (string) $psrRequest->getUri()->withUserInfo('')->withQuery('')->withFragment('');
        $responseStream = $response?->getPsrResponse()->getBody();

        RecordApiCall::make()->execute(
            direction: ApiCallDirection::Outbound,
            method: $pendingRequest->getMethod()->value,
            url: $url,
            outcome: $response instanceof Response ? ApiCallOutcome::fromStatus($response->status()) : ApiCallOutcome::ConnectionFailed,
            durationMs: $durationMs,
            status: $response?->status(),
            apiClient: ApiClient::query()->where('service', $this->service())->first(),
            service: $this->service(),
            resource: $request instanceof ServiceRequest ? $request->resource() : null,
            requestSize: $psrRequest->getBody()->getSize(),
            responseSize: $responseStream?->getSize(),
            requestBody: $logsBodies ? $this->requestBody($pendingRequest, $psrRequest) : null,
            responseBody: $logsBodies ? $this->responseBody($response) : null,
            error: $error === null ? null : str($error)->replace((string) $psrRequest->getUri(), $url)->value(),
        );
    }

    /**
     * A form body goes to the API log as JSON, so that its listed keys are masked too. A multipart body can hold files, and a stream body can be read only once, so neither is kept.
     */
    private function requestBody(PendingRequest $pendingRequest, RequestInterface $psrRequest): ?string
    {
        $body = $pendingRequest->body();

        if (! $body instanceof BodyRepository || $body instanceof MultipartBodyRepository || $body instanceof StreamBodyRepository) {
            return null;
        }

        if ($body instanceof FormBodyRepository) {
            return (string) json_encode($body->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $psrRequest->getBody();
    }

    private function responseBody(?Response $response): ?string
    {
        if (! $response instanceof Response) {
            return null;
        }

        $stream = $response->getPsrResponse()->getBody();
        $size = $stream->getSize();

        if (! $stream->isSeekable() || $size === null || $size > config()->integer('system.api_log.masked_body_limit')) {
            return null;
        }

        return $response->body();
    }
}
