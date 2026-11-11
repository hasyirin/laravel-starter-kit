<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Channels;

use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Support\CurrentMessage;
use App\Domain\Shared\Sms\Contracts\SmsGateway;
use App\Domain\Shared\Sms\Contracts\SmsNotification;
use App\Domain\Shared\Sms\Exceptions\CallingCodeNotAllowed;
use App\Domain\Shared\Sms\Support\CallingCodes;
use App\Domain\Shared\Sms\Support\SmsIntercept;
use App\Domain\User\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

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

        if (SmsIntercept::enabled()) {
            return $this->sendIntercepted($text);
        }

        $id = resolve(SmsGateway::class)->send($phone, $text);

        $current->sent($id);

        return $id;
    }

    private function sendIntercepted(#[SensitiveParameter] string $text): ?string
    {
        $current = resolve(CurrentMessage::class);
        $phone = SmsIntercept::phone();

        if ($phone === null) {
            logger()->info('SMS intercept dropped an SMS because it has no phone number.', ['message' => $current->message?->id]);
            $current->fail(MessageFailureReason::Dropped, 'The SMS intercept stopped the SMS, because it has no phone number to send it to.');

            return null;
        }

        throw_unless(CallingCodes::allows($phone), CallingCodeNotAllowed::class, 'The SMS intercept sends SMS only to the calling codes in system.sms.calling_codes.');

        $id = resolve(SmsGateway::class)->send($phone, $text);

        $current->intercept($phone);
        $current->sent($id);

        return $id;
    }
}
