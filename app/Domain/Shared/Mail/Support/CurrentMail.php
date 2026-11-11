<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Support;

use App\Domain\Shared\Mail\Models\Mail;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Queue\Job;

/**
 * Holds the log row of the notification email that is being sent. The mail events
 * that come between NotificationSending and NotificationSent do not carry the notification.
 */
#[Scoped]
class CurrentMail
{
    public ?Mail $mail = null;

    public SensitiveValues $sensitive;

    public bool $logged = false;

    public ?Job $job = null;

    public function __construct()
    {
        $this->sensitive = new SensitiveValues([]);
    }

    public function start(Mail $mail, SensitiveValues $sensitive, bool $logged): void
    {
        $this->mail = $mail;
        $this->sensitive = $sensitive;
        $this->logged = $logged;
    }

    public function end(): void
    {
        $this->mail = null;
        $this->sensitive = new SensitiveValues([]);
        $this->logged = false;
    }
}
