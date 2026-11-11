<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Listeners;

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Mail\Actions\TrackMail;
use App\Domain\Shared\Mail\Support\Recipients;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\Shared\Message\Contracts\HasMessageLog;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Jobs\ResendMessage;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Message\Support\CurrentMessage;
use App\Domain\Shared\Message\Support\SensitiveValues;
use App\Domain\User\Models\User;
use Hasyirin\Actor\Facades\Actor;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
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
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

class RecordMail
{
    public function handleNotificationSending(NotificationSending $event): void
    {
        $notifiable = $event->notifiable;

        if ($event->channel !== 'mail' || ! is_object($notifiable)) {
            return;
        }

        $notification = $event->notification;
        $logged = $notification instanceof HasMessageLog;
        $sensitive = $logged ? $notification->sensitive($notifiable) : [];

        $message = Message::query()->firstOrNew(['notification_id' => $notification->id]);
        $message->fill([
            'notification_type' => $notification::class,
            'payload' => $logged && $sensitive === [] ? serialize($notification) : null,
            'status' => MessageStatus::Sending,
            'error' => null,
            'failed_at' => null,
            'resend_of_id' => $logged ? $notification->resendOfId : null,
        ]);
        $message->forceFill(['tracked' => $logged && $notification->tracked($notifiable)]);

        if ($notifiable instanceof Model && ! $this->redirected($notification)) {
            $message->notifiable()->associate($notifiable);
        }

        $resource = $logged ? $notification->about() : null;

        if ($resource !== null) {
            $message->resource()->associate($resource);
        }

        $message->save();

        if ($message->wasRecentlyCreated) {
            $this->recordPreparer($message);
        }

        resolve(CurrentMessage::class)->start($message, new SensitiveValues($sensitive), $logged);
    }

    public function handleMessageSending(MessageSending $event): void
    {
        $current = resolve(CurrentMessage::class);
        $message = $current->message;

        if ($message === null) {
            return;
        }

        $email = $event->message;
        $to = collect(Recipients::to($email));

        $message->fill([
            'recipient_address' => $to->first()?->getAddress(),
            'recipient_name' => $to->first()?->getName() ?: null,
            'cc' => Recipients::toArray(Recipients::cc($email)),
            'bcc' => Recipients::toArray(Recipients::bcc($email)),
            'subject' => $current->sensitive->mask($email->getSubject()),
        ]);

        if ($current->logged && $to->count() !== 1) {
            $message->save();

            throw new LogicException('A notification email must have exactly one To recipient. Put the other people in CC or BCC.');
        }

        if ($current->logged) {
            $message->html_body = $current->sensitive->mask($email->getHtmlBody());
            $message->text_body = $current->sensitive->mask($email->getTextBody());
            $this->copyAttachments($message, $email);
        }

        $message->save();

        if ($message->tracked) {
            TrackMail::make()->execute($message, $email, $current->sensitive);
        }
    }

    public function handleMessageSent(MessageSent $event): void
    {
        $email = $event->sent->getOriginalMessage();

        if (! $email instanceof Email) {
            return;
        }

        $interceptedTo = Recipients::intercepted($email) ? $email->getTo()[0]->getAddress() : null;
        $message = resolve(CurrentMessage::class)->message;

        $recipients = collect(Recipients::to($email) ?: [null]);

        if ($message !== null) {
            $message->markAsSent($event->sent->getMessageId(), $interceptedTo);
            $recipients = $recipients->skip(1);
        }

        $recipients->each(fn (?Address $recipient) => $this->recordPlainEmail($email, $recipient, $event->sent->getMessageId(), $interceptedTo));
    }

    public function handleNotificationSent(NotificationSent $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $current = resolve(CurrentMessage::class);

        if ($event->response === null && $current->message?->status === MessageStatus::Sending) {
            $current->message->fail($this->unaddressed($event)
                ? ['type' => 'no_address', 'code' => 0, 'message' => 'The recipient has no email address.']
                : ['type' => 'dropped', 'code' => 0, 'message' => 'A mail listener stopped the email before it was sent.']);
        }

        $current->end();
    }

    public function handleNotificationFailed(NotificationFailed $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $current = resolve(CurrentMessage::class);
        $exception = $event->data['exception'] ?? null;

        if (! $exception instanceof Throwable) {
            $current->end();

            return;
        }

        // A queue job tries again, and its failed() hook marks the row as Failed after the last try.
        // A LogicException is a mistake in the code, so another try cannot succeed.
        if ($current->job !== null && $event->notification instanceof HasMessageLog && ! $exception instanceof LogicException) {
            $current->message?->recordError($exception);
        } else {
            $current->message?->markAsFailed($exception);
        }

        if ($exception instanceof LogicException) {
            $current->job?->fail($exception);
        }

        $current->end();
    }

    public function handleNotificationSkipped(NotificationSkipped $event): void
    {
        $current = resolve(CurrentMessage::class);
        $message = $current->message;

        if ($event->channel !== 'mail' || $message === null || $message->notification_id !== $event->notification->id) {
            return;
        }

        $message->fail(['type' => 'dropped', 'code' => 0, 'message' => 'A notification listener stopped the email before it was sent.']);
        $current->end();
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        if ($this->sendsMail($event->job)) {
            resolve(CurrentMessage::class)->job = $event->job;
        }
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        $this->forgetJob($event->job);
    }

    public function handleJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        $this->forgetJob($event->job);
    }

    private function sendsMail(Job $job): bool
    {
        return in_array(data_get($job->payload(), 'data.commandName'), [SendQueuedNotifications::class, ResendMessage::class], true);
    }

    private function forgetJob(Job $job): void
    {
        $current = resolve(CurrentMessage::class);

        if ($current->job === $job) {
            $current->job = null;
        }
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

    private function recordPreparer(Message $message): void
    {
        if (auth(config('actor.guard'))->check()) {
            return;
        }

        $user = User::withTrashed()->whereKey(context()->get('user_id'))->first();

        if ($user !== null) {
            Actor::act($message, Act::Prepare, $user);
        }
    }

    private function recordPlainEmail(Email $email, ?Address $recipient, ?string $providerId, ?string $interceptedTo): void
    {
        $message = Message::query()->create([
            'recipient_address' => $recipient?->getAddress(),
            'recipient_name' => $recipient?->getName() ?: null,
            'cc' => Recipients::toArray(Recipients::cc($email)),
            'bcc' => Recipients::toArray(Recipients::bcc($email)),
            'subject' => $email->getSubject(),
            'provider_id' => $providerId,
            'intercepted_to' => $interceptedTo,
            'status' => MessageStatus::Sent,
            'sent_at' => now(),
        ]);

        $this->recordPreparer($message);
    }

    private function copyAttachments(Message $message, Email $email): void
    {
        $message->clearMediaCollection(MediaCollection::Attachments);

        collect($email->getAttachments())
            ->reject(fn (DataPart $attachment): bool => $attachment->getDisposition() === 'inline')
            ->each(fn (DataPart $attachment): mixed => $message
                ->addMediaFromString($attachment->getBody())
                ->usingFileName($attachment->getFilename() ?? 'attachment')
                ->toMediaCollection(MediaCollection::Attachments->value));
    }
}
