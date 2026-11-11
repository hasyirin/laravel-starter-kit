<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Channels;

use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Support\CurrentMessage;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\Shared\Sms\Contracts\SmsNotification;
use App\Domain\Shared\Sms\Exceptions\CallingCodeNotAllowed;
use App\Domain\Shared\Sms\Support\CallingCodes;
use App\Domain\User\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function send(User|AnonymousNotifiable $notifiable, Notification&SmsNotification $notification): ?string
    {
        $phone = $notifiable->routeNotificationFor('sms', $notification);
        $current = resolve(CurrentMessage::class);

        if (! is_string($phone) || $phone === '') {
            $current->fail(MessageFailureReason::NoPhoneNumber, 'The recipient has no phone number.');

            return null;
        }

        $text = $notification->toSms($notifiable);

        $current->record($phone, $text);

        throw_unless(CallingCodes::allows($phone), CallingCodeNotAllowed::class);

        $id = resolve(SmsGateway::class)->send($phone, $text);

        $current->sent($id);

        return $id;
    }
}
