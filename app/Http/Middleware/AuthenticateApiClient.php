<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $failureKey = 'api-authentication|'.$request->ip();

        if (RateLimiter::tooManyAttempts($failureKey, config()->integer('system.api.failed_authentication_per_minute'))) {
            throw new ThrottleRequestsException(headers: ['Retry-After' => RateLimiter::availableIn($failureKey)]);
        }

        auth()->shouldUse('api');

        $apiClient = auth('api')->user();

        if ($apiClient === null) {
            RateLimiter::hit($failureKey);

            throw new AuthenticationException(guards: ['api']);
        }

        if (! $apiClient->enabled) {
            $this->reject($request, ApiCallOutcome::ClientDisabled);
        }

        if (! $apiClient->allowsIpAddress((string) $request->ip())) {
            $this->reject($request, ApiCallOutcome::IpNotAllowed);
        }

        return $next($request);
    }

    private function reject(Request $request, ApiCallOutcome $outcome): never
    {
        $request->attributes->set(ApiCallOutcome::class, $outcome);

        abort(Response::HTTP_FORBIDDEN);
    }
}
