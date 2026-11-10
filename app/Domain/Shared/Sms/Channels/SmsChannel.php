<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Channels;

use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\Shared\Sms\Contracts\SmsNotification;
use App\Domain\Shared\Sms\Support\CallingCodes;
use App\Domain\User\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use LogicException;

class SmsChannel
{
    public function send(User|AnonymousNotifiable $notifiable, Notification&SmsNotification $notification): void
    {
        $phone = $notifiable->routeNotificationFor('sms', $notification);

        if (! is_string($phone) || $phone === '') {
            return;
        }

        throw_unless(CallingCodes::allows($phone), LogicException::class, 'The app sends SMS only to the calling codes in system.sms.calling_codes.');

        resolve(SmsGateway::class)->send($phone, $notification->toSms($notifiable));
    }
}
