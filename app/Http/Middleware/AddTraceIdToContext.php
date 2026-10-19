<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddTraceIdToContext
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->headers->get('X-Request-Id');
        $traceId = str($incoming)->isMatch('/\A[\w.-]{1,128}\z/') ? $incoming : (string) Str::uuid7();

        context()->add('trace_id', $traceId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $traceId);

        return $response;
    }
}
