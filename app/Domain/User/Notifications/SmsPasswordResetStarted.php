<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SmsPasswordResetStarted extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject((string) __('auth/sms-password-reset.mail.subject'))
            ->line((string) __('auth/sms-password-reset.mail.line'))
            ->line((string) __('auth/sms-password-reset.mail.warning'));
    }
}
