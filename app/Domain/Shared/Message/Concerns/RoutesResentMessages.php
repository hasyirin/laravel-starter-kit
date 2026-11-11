<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Concerns;

use App\Domain\Shared\Message\Contracts\HasMessageLog;
use Illuminate\Notifications\Notification;

trait RoutesResentMessages
{
    public function routeNotificationForMail(Notification $notification): string
    {
        return $this->resendAddress($notification) ?? (string) $this->getAttribute('email');
    }

    public function routeNotificationForSms(?Notification $notification = null): ?string
    {
        $phone = $this->resendAddress($notification) ?? $this->getAttribute('phone');

        if (! is_string($phone)) {
            return null;
        }

        return $phone;
    }

    private function resendAddress(?Notification $notification): ?string
    {
        if (! $notification instanceof HasMessageLog) {
            return null;
        }

        return $notification->resendTo;
    }
}
