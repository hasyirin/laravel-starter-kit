<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use App\Domain\Shared\Message\Concerns\InteractsWithMessageLog;
use App\Domain\Shared\Message\Contracts\HasMessageLog;
use App\Domain\Shared\Sms\Contracts\SmsNotification;
use App\Domain\User\Support\SmsCode;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

class PhoneVerificationCodeSms extends Notification implements HasMessageLog, SmsNotification
{
    use InteractsWithMessageLog;

    public function __construct(#[SensitiveParameter] public readonly string $code) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['sms'];
    }

    /**
     * @return list<string>
     */
    public function sensitive(object $notifiable): array
    {
        return [$this->code];
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
