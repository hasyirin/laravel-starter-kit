<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordWasReset extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject((string) __('auth/reset-password.alert.subject'))
            ->line((string) __('auth/reset-password.alert.line'))
            ->line((string) __('auth/reset-password.alert.warning'));
    }
}
