<?php

declare(strict_types=1);

namespace Tests\Fixtures\Notifications;

use App\Domain\Shared\Sms\Channels\SmsChannel;
use App\Domain\Shared\Sms\Contracts\SmsNotification;
use Illuminate\Notifications\Notification;

class TextMessage extends Notification implements SmsNotification
{
    public function __construct(public string $text) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return $this->text;
    }
}
