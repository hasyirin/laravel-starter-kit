<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\SessionGuard;

class ForgetRememberCookie
{
    public function handle(Login $event): void
    {
        if ($event->guard !== config()->string('fortify.guard')) {
            return;
        }

        $guard = auth()->guard($event->guard);

        if (! $guard instanceof SessionGuard) {
            return;
        }

        cookie()->expire($guard->getRecallerName());
    }
}
