<?php

declare(strict_types=1);

namespace App\Domain\Shared\Sms\Contracts;

interface SmsNotification
{
    public function toSms(object $notifiable): string;
}
