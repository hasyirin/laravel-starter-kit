<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\Models\User;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as LaravelEnsureEmailIsVerified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsureEmailIsVerified extends LaravelEnsureEmailIsVerified
{
    /**
     * @param  Request  $request
     * @param  Closure(Request): (Response|RedirectResponse|null)  $next
     * @param  string|null  $redirectToRoute
     * @return Response|RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        $user = $request->user();

        if ($user instanceof User && $user->checkPermissionTo(config()->string('system.permissions.bypass-authorization'))) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
