<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddIpAddressAndUserAgentToAuditLog
{
    private const int USER_AGENT_LIMIT = 512;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->add([
            'ip_address' => $request->ip(),
            'user_agent' => $this->userAgent($request),
        ]);

        return $next($request);
    }

    private function userAgent(Request $request): ?string
    {
        $userAgent = $request->userAgent();

        if ($userAgent === null) {
            return null;
        }

        return str(mb_scrub($userAgent, 'UTF-8'))->substr(0, self::USER_AGENT_LIMIT)->value();
    }
}
