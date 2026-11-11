<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Jobs;

use App\Domain\Shared\Message\Contracts\HasMessageLog;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifications;
use Throwable;

class ResendMessage implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public object $notifiable, public Notification $notification, public string $channel)
    {
        if ($channel === 'mail') {
            $this->onConnection(config()->string('mail.queue_connection'));
        }
    }

    public function handle(): void
    {
        Notifications::sendNow($this->notifiable, $this->notification, [$this->channel]);
    }

    public function failed(Throwable $exception): void
    {
        if ($this->notification instanceof HasMessageLog) {
            $this->notification->failed($exception);
        }
    }
}
