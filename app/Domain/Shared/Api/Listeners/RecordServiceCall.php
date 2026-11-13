<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Listeners;

use App\Domain\Shared\Api\Actions\RecordApiCall;
use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiClient;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

/**
 * Records each attempt of a call that the service entry point tagged. The client sends one attempt at a time,
 * so the start of the last attempt gives the duration.
 */
#[Scoped]
class RecordServiceCall
{
    private ?float $startedAt = null;

    public function handleRequestSending(RequestSending $event): void
    {
        if ($this->service($event->request) === null) {
            return;
        }

        $this->startedAt = microtime(true);
    }

    public function handleResponseReceived(ResponseReceived $event): void
    {
        $service = $this->service($event->request);

        if ($service === null) {
            return;
        }

        $this->record($event->request, $service, $event->response);
    }

    public function handleConnectionFailed(ConnectionFailed $event): void
    {
        $service = $this->service($event->request);

        if ($service === null) {
            return;
        }

        $uri = $event->request->toPsrRequest()->getUri();
        $error = str($event->exception->getMessage())->replace((string) $uri, $this->url($event->request))->value();

        $this->record($event->request, $service, error: $error);
    }

    private function record(Request $request, string $service, ?Response $response = null, ?string $error = null): void
    {
        $logsBodies = config()->boolean("system.api.services.{$service}.log_bodies", false);
        $resource = data_get($request->attributes(), 'api_log.resource');

        RecordApiCall::make()->execute(
            direction: ApiCallDirection::Outbound,
            method: $request->method(),
            url: $this->url($request),
            outcome: $response instanceof Response ? ApiCallOutcome::fromStatus($response->status()) : ApiCallOutcome::ConnectionFailed,
            durationMs: $this->elapsedMs(),
            status: $response?->status(),
            apiClient: ApiClient::query()->where('service', $service)->oldest('id')->first(),
            service: $service,
            resource: $resource instanceof Model ? $resource : null,
            requestSize: $request->toPsrRequest()->getBody()->getSize(),
            responseSize: $response instanceof Response ? strlen($response->body()) : null,
            requestBody: $logsBodies ? $this->requestBody($request) : null,
            responseBody: $logsBodies ? $response?->body() : null,
            error: $error,
        );
    }

    private function elapsedMs(): int
    {
        if ($this->startedAt === null) {
            return 0;
        }

        return (int) round((microtime(true) - $this->startedAt) * 1000);
    }

    private function service(Request $request): ?string
    {
        $service = data_get($request->attributes(), 'api_log.service');

        if (! is_string($service)) {
            return null;
        }

        return $service;
    }

    /**
     * The user info and the query string can carry credentials, so the API log keeps the URL without them.
     */
    private function url(Request $request): string
    {
        return (string) $request->toPsrRequest()->getUri()->withUserInfo('')->withQuery('')->withFragment('');
    }

    /**
     * A form body goes to the API log as JSON, so that its listed keys are masked too. A multipart body can hold files, so it is not kept.
     */
    private function requestBody(Request $request): ?string
    {
        if ($request->isMultipart()) {
            return null;
        }

        if ($request->isForm()) {
            return (string) json_encode($request->data(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $request->body();
    }
}
