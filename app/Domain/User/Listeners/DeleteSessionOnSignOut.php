<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Models\UserSession;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Logout;

class DeleteSessionOnSignOut
{
    public function handle(Logout|CurrentDeviceLogout $event): void
    {
        if ($event->guard !== config()->string('fortify.guard')) {
            return;
        }

        UserSession::current()?->forceDelete();

        session()->forget(UserSession::SESSION_KEY);
    }
}
