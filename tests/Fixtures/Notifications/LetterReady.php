<?php

declare(strict_types=1);

namespace Tests\Fixtures\Notifications;

use App\Domain\Shared\Message\Notifications\Notification;
use App\Domain\Shared\Sms\Contracts\SmsNotification;
use Illuminate\Notifications\Messages\MailMessage;

class LetterReady extends Notification implements SmsNotification
{
    /**
     * @param  list<string>  $channels
     */
    public function __construct(public ?string $secret = null, public array $channels = ['sms']) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * @return list<string>
     */
    public function sensitive(object $notifiable): array
    {
        if ($this->secret === null) {
            return [];
        }

        return [$this->secret];
    }

    public function toSms(object $notifiable): string
    {
        if ($this->secret === null) {
            return 'Your letter is ready.';
        }

        return "Your letter is ready. Collect it with code {$this->secret}.";
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()->subject('Letter ready')->line('Your letter is ready.');
    }
}
