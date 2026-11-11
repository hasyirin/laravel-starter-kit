<?php

declare(strict_types=1);

namespace Tests\Fixtures\Notifications;

use App\Domain\Shared\Message\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class LetterIssued extends Notification
{
    /**
     * @param  list<string>  $cc
     * @param  list<string>  $bcc
     */
    public function __construct(
        public ?Model $letter = null,
        public ?string $secret = null,
        public bool $tracking = true,
        public array $cc = [],
        public array $bcc = [],
        public bool $attached = false,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function tracked(object $notifiable): bool
    {
        return $this->tracking;
    }

    /**
     * @return list<string>
     */
    public function sensitive(object $notifiable): array
    {
        return $this->secret === null ? [] : [$this->secret];
    }

    public function about(): ?Model
    {
        return $this->letter;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = new MailMessage()
            ->subject($this->secret === null ? 'Letter issued' : 'Letter issued with code '.$this->secret)
            ->line('Your code is '.($this->secret ?? 'none').'.')
            ->line(new HtmlString('Write to <a href="mailto:help@example.com">help</a> or read <a href="https://example.org/guide?a=1&amp;b=2">the guide</a>.'))
            ->action('Open letter', route('settings'))
            ->cc($this->cc)
            ->bcc($this->bcc);

        if ($this->attached) {
            $message->attachData('letter content', 'letter.pdf', ['mime' => 'application/pdf']);
        }

        return $message;
    }
}
