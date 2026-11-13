<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Api\Actions\RecordApiCall;
use App\Domain\Shared\Api\Enums\ApiCallDirection;
use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Guards\ApiTokenGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordInboundApiCall
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->is('api/*')) {
            return;
        }

        /** @var ApiTokenGuard $guard */
        $guard = auth()->guard('api');
        $apiToken = $guard->apiToken();
        $responseBody = $response->getContent();
        $logsBodies = $request->attributes->getBoolean(LogApiCallBodies::class);

        RecordApiCall::make()->execute(
            direction: ApiCallDirection::Inbound,
            method: str($request->method())->substr(0, 10)->value(),
            url: $request->getPathInfo(),
            outcome: $this->outcome($request, $response, $guard),
            durationMs: (int) round((microtime(true) - (float) $request->server('REQUEST_TIME_FLOAT')) * 1000),
            status: $response->getStatusCode(),
            apiClient: $apiToken->apiClient ?? $apiToken?->apiClient()->withTrashed()->first(),
            apiToken: $apiToken,
            routeName: $request->route()?->getName(),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            requestSize: $this->requestSize($request),
            responseSize: is_string($responseBody) ? strlen($responseBody) : null,
            requestBody: $logsBodies ? $request->getContent() : null,
            responseBody: $logsBodies && is_string($responseBody) ? $responseBody : null,
        );
    }

    private function outcome(Request $request, Response $response, ApiTokenGuard $guard): ApiCallOutcome
    {
        $outcome = $request->attributes->get(ApiCallOutcome::class);

        if ($outcome instanceof ApiCallOutcome) {
            return $outcome;
        }

        return $guard->failure() ?? ApiCallOutcome::fromStatus($response->getStatusCode());
    }

    private function requestSize(Request $request): ?int
    {
        $contentLength = $request->header('Content-Length');

        if (! is_string($contentLength) || ! ctype_digit($contentLength)) {
            return null;
        }

        return (int) $contentLength;
    }
}
