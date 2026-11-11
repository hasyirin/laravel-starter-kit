<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Listeners;

use App\Domain\Shared\Message\Contracts\HasMessageLog;
use App\Domain\Shared\Message\Enums\MessageFailureReason;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Jobs\ResendMessage;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Message\Support\CurrentMessage;
use App\Domain\Shared\Message\Support\SensitiveValues;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Events\NotificationSkipped;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use LogicException;
use Throwable;

class RecordMessage
{
    /**
     * These channels carry the notification under the bell. The message log does not hold it.
     */
    private const array BELL_CHANNELS = ['database', 'broadcast'];

    public function handleNotificationSending(NotificationSending $event): void
    {
        $current = resolve(CurrentMessage::class);

        // A queue job sends on one channel, also on a channel that the log does not hold.
        if ($current->job instanceof Job && $current->jobChannel === null) {
            $current->jobChannel = $event->channel;
        }

        $notifiable = $event->notifiable;

        if (in_array($event->channel, self::BELL_CHANNELS, true) || ! is_object($notifiable)) {
            return;
        }

        $notification = $event->notification;
        $logged = $notification instanceof HasMessageLog;
        $sensitive = $logged ? $notification->sensitive($notifiable) : [];

        $message = Message::query()->firstOrNew(['notification_id' => $notification->id, 'channel' => $event->channel]);
        $message->fill([
            'notification_type' => $notification::class,
            'payload' => $logged && $sensitive === [] ? serialize($notification) : null,
            'status' => MessageStatus::Sending,
            'error' => null,
            'failed_at' => null,
            'resend_of_id' => $logged ? $notification->resendOfId : null,
        ]);
        $message->forceFill(['tracked' => $logged && $event->channel === 'mail' && $notification->tracked($notifiable)]);

        if ($notifiable instanceof Model && ! $this->redirected($notification)) {
            $message->notifiable()->associate($notifiable);
        }

        $resource = $logged ? $notification->about() : null;

        if ($resource !== null) {
            $message->resource()->associate($resource);
        }

        $message->save();

        if ($message->wasRecentlyCreated) {
            $message->recordPreparerFromContext();
        }

        $current->start($message, new SensitiveValues($sensitive), $logged);
    }

    public function handleNotificationSent(NotificationSent $event): void
    {
        $current = resolve(CurrentMessage::class);
        $message = $current->forChannel($event->channel);

        if ($message === null) {
            return;
        }

        if ($message->status === MessageStatus::Sending) {
            $this->conclude($message, $event);
        }

        $current->end();
    }

    public function handleNotificationFailed(NotificationFailed $event): void
    {
        $current = resolve(CurrentMessage::class);
        $message = $current->forChannel($event->channel);

        if ($message === null) {
            return;
        }

        $exception = $event->data['exception'] ?? null;

        if (! $exception instanceof Throwable) {
            $message->failBecause(MessageFailureReason::Unknown, 'The channel reported that the send failed.');
            $current->end();

            return;
        }

        // A queue job tries again, and its failed() hook marks the row as Failed after the last try.
        // A LogicException stays true on each try, so another try cannot succeed.
        if ($current->job !== null && $event->notification instanceof HasMessageLog && ! $exception instanceof LogicException) {
            $message->recordError($exception, $current->sensitive);
        } else {
            $message->markAsFailed($exception, $current->sensitive);
        }

        if ($exception instanceof LogicException) {
            $current->job?->fail($exception);
        }

        $current->end();
    }

    public function handleNotificationSkipped(NotificationSkipped $event): void
    {
        $current = resolve(CurrentMessage::class);
        $message = $current->forChannel($event->channel);

        if ($message === null || $message->notification_id !== $event->notification->id) {
            return;
        }

        $message->failBecause(MessageFailureReason::Dropped, 'A notification listener stopped the message before it was sent.');
        $current->end();
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        if ($this->sendsMessages($event->job)) {
            resolve(CurrentMessage::class)->job = $event->job;
        }
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        resolve(CurrentMessage::class)->forgetJob($event->job);
    }

    public function handleJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        resolve(CurrentMessage::class)->forgetJob($event->job);
    }

    private function conclude(Message $message, NotificationSent $event): void
    {
        // A channel of the kit marks its own row as sent. A channel that an app adds says nothing.
        if ($event->channel !== 'mail') {
            $message->markAsSent(null);

            return;
        }

        // The mailer gives no response when it sent nothing.
        if ($event->response !== null) {
            return;
        }

        if ($this->unaddressed($event)) {
            $message->failBecause(MessageFailureReason::NoAddress, 'The recipient has no email address.');

            return;
        }

        $message->failBecause(MessageFailureReason::Dropped, 'A mail listener stopped the email before it was sent.');
    }

    private function sendsMessages(Job $job): bool
    {
        return in_array(data_get($job->payload(), 'data.commandName'), [SendQueuedNotifications::class, ResendMessage::class], true);
    }

    private function redirected(Notification $notification): bool
    {
        if (! $notification instanceof HasMessageLog || $notification->resendOfId === null || $notification->resendTo === null) {
            return false;
        }

        $original = Message::query()->whereKey($notification->resendOfId)->value('recipient_address');

        return ! is_string($original) || strcasecmp($original, $notification->resendTo) !== 0;
    }

    private function unaddressed(NotificationSent $event): bool
    {
        $notifiable = $event->notifiable;

        return is_object($notifiable) && method_exists($notifiable, 'routeNotificationFor') && blank($notifiable->routeNotificationFor('mail', $event->notification));
    }
}
