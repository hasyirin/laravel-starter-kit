<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Concerns;

use App\Domain\Shared\Mail\Contracts\HasMailLog;
use Illuminate\Notifications\Notification;

trait RoutesResentMail
{
    public function routeNotificationForMail(Notification $notification): string
    {
        if ($notification instanceof HasMailLog && $notification->resendTo !== null) {
            return $notification->resendTo;
        }

        return (string) $this->getAttribute('email');
    }
}
