<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ProblemDetails
{
    public static function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') || $exception instanceof HttpResponseException) {
            return null;
        }

        [$status, $headers] = match (true) {
            $exception instanceof AuthenticationException => [Response::HTTP_UNAUTHORIZED, ['WWW-Authenticate' => 'Bearer']],
            $exception instanceof ValidationException => [$exception->status, []],
            $exception instanceof HttpExceptionInterface => [$exception->getStatusCode(), $exception->getHeaders()],
            default => [Response::HTTP_INTERNAL_SERVER_ERROR, []],
        };

        $title = Response::$statusTexts[$status] ?? 'Error';

        $problem = collect([
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
            'detail' => self::detail($status, $title, $exception),
            'trace_id' => context()->get('trace_id'),
        ]);

        if ($exception instanceof ValidationException) {
            $problem->put('errors', $exception->errors());
        }

        return response()->json($problem->all(), $status, [...$headers, 'Content-Type' => 'application/problem+json']);
    }

    private static function detail(int $status, string $title, Throwable $exception): string
    {
        if ($status === Response::HTTP_INTERNAL_SERVER_ERROR && config()->boolean('app.debug')) {
            return $exception->getMessage();
        }

        if (! trans()->has("api/problems.$status")) {
            return $title;
        }

        return (string) __("api/problems.$status");
    }
}
