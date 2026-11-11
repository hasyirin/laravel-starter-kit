<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\Contracts\Models\MustVerifyPhone;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof MustVerifyPhone || $user->hasVerifiedPhone()) {
            return $next($request);
        }

        if ($user->checkPermissionTo(config()->string('system.permissions.bypass-authorization'))) {
            return $next($request);
        }

        abort_if($request->expectsJson(), 403, 'Your phone number is not verified.');

        return redirect()->guest(route('phone-verification.notice'));
    }
}
