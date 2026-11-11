<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Support;

use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Models\Message;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Collection;
use SensitiveParameter;

/**
 * Holds the log row of the message that is being sent. A channel gives its facts to this row,
 * because the events between NotificationSending and NotificationSent do not carry the notification.
 */
#[Scoped]
class CurrentMessage
{
    public ?Message $message = null;

    public SensitiveValues $sensitive;

    public bool $logged = false;

    public ?Job $job = null;

    /**
     * The channel that the queue job sends on. It stays until the job ends, so the failed() hook can read it.
     */
    public ?string $jobChannel = null;

    /**
     * The messages that were in flight when another message started inside their send.
     *
     * @var Collection<int, array{Message, SensitiveValues, bool}>
     */
    private Collection $outer;

    public function __construct()
    {
        $this->sensitive = new SensitiveValues([]);
        $this->outer = new Collection;
    }

    public function start(Message $message, SensitiveValues $sensitive, bool $logged): void
    {
        if ($this->message instanceof Message) {
            $this->outer->push([$this->message, $this->sensitive, $this->logged]);
        }

        $this->message = $message;
        $this->sensitive = $sensitive;
        $this->logged = $logged;
    }

    public function end(): void
    {
        [$this->message, $this->sensitive, $this->logged] = $this->outer->last() ?? [null, new SensitiveValues([]), false];
        $this->outer = $this->outer->slice(0, -1);
    }

    public function forgetJob(Job $job): void
    {
        if ($this->job !== $job) {
            return;
        }

        $this->job = null;
        $this->jobChannel = null;
    }

    public function forChannel(string $channel): ?Message
    {
        if ($this->message?->channel !== $channel) {
            return null;
        }

        return $this->message;
    }

    public function record(#[SensitiveParameter] string $address, #[SensitiveParameter] string $text): void
    {
        if (! $this->message instanceof Message) {
            return;
        }

        $this->message->recipient_address = $address;

        if ($this->logged) {
            $this->message->text_body = $this->sensitive->mask($text);
        }

        $this->message->save();
    }

    public function intercept(#[SensitiveParameter] string $address): void
    {
        $this->message?->update(['intercepted_to' => $address]);
    }

    public function sent(?string $providerId = null): void
    {
        $this->message?->markAsSent($providerId);
    }

    public function fail(MessageFailureReason $reason, string $text): void
    {
        $this->message?->failBecause($reason, $text);
    }
}
