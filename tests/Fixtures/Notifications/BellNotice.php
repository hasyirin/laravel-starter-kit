<?php

declare(strict_types=1);

namespace Tests\Fixtures\Notifications;

use Illuminate\Notifications\Notification;

class BellNotice extends Notification
{
    public function __construct(public string $channel) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [$this->channel];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => 'Letter ready'];
    }
}
