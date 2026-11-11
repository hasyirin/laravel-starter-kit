<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Concerns;

use App\Domain\Shared\Message\Contracts\HasMessageLog;
use Illuminate\Notifications\Notification;

trait RoutesResentMail
{
    public function routeNotificationForMail(Notification $notification): string
    {
        if ($notification instanceof HasMessageLog && $notification->resendTo !== null) {
            return $notification->resendTo;
        }

        return (string) $this->getAttribute('email');
    }
}
