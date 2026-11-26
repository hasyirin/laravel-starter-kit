<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Models\UserSession;
use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Events\Login;

class ForgetPreviousSessionOnLogin
{
    public function handle(Login $event): void
    {
        if ($event->guard !== config()->string('fortify.guard') || Impersonation::becoming()) {
            return;
        }

        UserSession::current()?->forceDelete();

        session()->forget(UserSession::SESSION_KEY);
    }
}
