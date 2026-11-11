<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Support;

use App\Domain\Shared\Message\Models\Message;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Queue\Job;

/**
 * Holds the log row of the notification email that is being sent. The mail events
 * that come between NotificationSending and NotificationSent do not carry the notification.
 */
#[Scoped]
class CurrentMessage
{
    public ?Message $message = null;

    public SensitiveValues $sensitive;

    public bool $logged = false;

    public ?Job $job = null;

    public function __construct()
    {
        $this->sensitive = new SensitiveValues([]);
    }

    public function start(Message $message, SensitiveValues $sensitive, bool $logged): void
    {
        $this->message = $message;
        $this->sensitive = $sensitive;
        $this->logged = $logged;
    }

    public function end(): void
    {
        $this->message = null;
        $this->sensitive = new SensitiveValues([]);
        $this->logged = false;
    }
}
