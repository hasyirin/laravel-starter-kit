<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use App\Domain\Shared\Sms\Channels\SmsChannel;
use App\Domain\Shared\Sms\Contracts\SmsNotification;
use App\Domain\User\Support\SmsCode;
use Illuminate\Notifications\Notification;

class PhoneVerificationCodeSms extends Notification implements SmsNotification
{
    public function __construct(public readonly string $code) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return (string) __('user/phone.sms.verification', [
            'app' => config('app.name'),
            'code' => $this->code,
            'minutes' => SmsCode::MINUTES,
        ]);
    }
}
