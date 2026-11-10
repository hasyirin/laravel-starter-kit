<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Models\User;
use App\Domain\User\Notifications\PasswordWasReset;
use Illuminate\Auth\Events\PasswordReset;

class SendPasswordResetAlert
{
    public function handle(PasswordReset $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->notify(new PasswordWasReset);
    }
}
