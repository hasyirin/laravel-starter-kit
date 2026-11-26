<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\User\Actions\Session\ExpireSession;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use App\Domain\User\Support\Impersonation;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSession
{
    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = auth()->guard(config()->string('fortify.guard'));

        if (! $guard instanceof SessionGuard || ! $request->hasSession()) {
            return $next($request);
        }

        $session = null;

        if ($guard->check() && $request->session()->has(UserSession::SESSION_KEY)) {
            $session = $this->ensureLive($request, $guard);
        }

        $response = $next($request);

        $user = $guard->user();

        if ($user instanceof User) {
            $this->track($request, $user, $session);
        }

        return $response;
    }

    /**
     * @throws AuthenticationException
     */
    private function ensureLive(Request $request, SessionGuard $guard): UserSession
    {
        $session = UserSession::current();

        if (! $session instanceof UserSession) {
            $this->signOut($request, $guard);
        }

        if ($session->past_maximum) {
            ExpireSession::make()->execute($session);

            $this->signOut($request, $guard);
        }

        return $session;
    }

    /**
     * Sign out as the auth.session middleware does, so no sign-out entry is written.
     *
     * @throws AuthenticationException
     */
    private function signOut(Request $request, SessionGuard $guard): never
    {
        $guard->logoutCurrentDevice();

        $request->session()->invalidate();

        throw new AuthenticationException('Unauthenticated.', [config()->string('fortify.guard')], route('login'));
    }

    /**
     * A row that this request deleted stays deleted, and the next request signs the session out.
     * A save() of another instance of that row updates no row.
     */
    private function track(Request $request, User $user, ?UserSession $session): void
    {
        $values = [
            'session_id' => $request->session()->getId(),
            'user_id' => $user->id,
            'impersonator_id' => Impersonation::impersonatorId(),
        ];
        $activity = [
            'last_activity_at' => now(),
            'ip_address' => $request->attributes->get('ip_address'),
            'user_agent' => $request->attributes->get('user_agent'),
        ];

        if (! $request->session()->has(UserSession::SESSION_KEY)) {
            $session = UserSession::query()->create([...$values, ...$activity, 'signed_in_at' => now()]);
            $request->session()->put(UserSession::SESSION_KEY, $session->id);

            return;
        }

        $session ??= UserSession::current();

        if (! $session instanceof UserSession || ! $session->exists) {
            return;
        }

        $session->fill($values);

        if ($session->last_activity_at->lte(now()->subSeconds(UserSession::WRITE_INTERVAL_SECONDS))) {
            $session->fill($activity);
        }

        $session->save();
    }
}
