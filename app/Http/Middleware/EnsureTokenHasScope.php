<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Api\Enums\ApiCallOutcome;
use App\Domain\Shared\Api\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenHasScope
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $apiClient = auth('api')->user();

        if (! $apiClient instanceof ApiClient || collect($scopes)->contains(fn (string $scope): bool => ! $apiClient->tokenCan($scope))) {
            $request->attributes->set(ApiCallOutcome::class, ApiCallOutcome::MissingScope);

            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
