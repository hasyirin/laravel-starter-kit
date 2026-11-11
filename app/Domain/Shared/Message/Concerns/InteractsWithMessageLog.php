<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Concerns;

use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use Illuminate\Database\Eloquent\Model;
use Throwable;

trait InteractsWithMessageLog
{
    public ?int $resendOfId = null;

    public ?string $resendTo = null;

    public int $tries = 4;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function tracked(object $notifiable): bool
    {
        return config()->boolean('mail.tracking');
    }

    /**
     * @return list<string>
     */
    public function sensitive(object $notifiable): array
    {
        return [];
    }

    public function about(): ?Model
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['mail' => config()->string('mail.queue_connection')];
    }

    public function failed(Throwable $exception): void
    {
        Message::query()->where('notification_id', $this->id)->where('status', MessageStatus::Sending)->first()?->markAsFailed($exception);
    }
}
