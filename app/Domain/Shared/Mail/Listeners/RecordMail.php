<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Listeners;

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Mail\Actions\TrackMail;
use App\Domain\Shared\Mail\Contracts\HasMailLog;
use App\Domain\Shared\Mail\Enums\MailStatus;
use App\Domain\Shared\Mail\Jobs\ResendMail;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\Shared\Mail\Support\CurrentMail;
use App\Domain\Shared\Mail\Support\Recipients;
use App\Domain\Shared\Mail\Support\SensitiveValues;
use App\Domain\Shared\Media\Enums\MediaCollection;
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
        $logged = $notification instanceof HasMailLog;
        $sensitive = $logged ? $notification->sensitive($notifiable) : [];

        $mail = Mail::query()->firstOrNew(['notification_id' => $notification->id]);
        $mail->fill([
            'notification_type' => $notification::class,
            'payload' => $logged && $sensitive === [] ? serialize($notification) : null,
            'status' => MailStatus::Sending,
            'error' => null,
            'failed_at' => null,
            'resend_of_id' => $logged ? $notification->resendOfId : null,
        ]);
        $mail->forceFill(['tracked' => $logged && $notification->tracked($notifiable)]);

        if ($notifiable instanceof Model && ! $this->redirected($notification)) {
            $mail->notifiable()->associate($notifiable);
        }

        $resource = $logged ? $notification->about() : null;

        if ($resource !== null) {
            $mail->resource()->associate($resource);
        }

        $mail->save();

        if ($mail->wasRecentlyCreated) {
            $this->recordPreparer($mail);
        }

        resolve(CurrentMail::class)->start($mail, new SensitiveValues($sensitive), $logged);
    }

    public function handleMessageSending(MessageSending $event): void
    {
        $current = resolve(CurrentMail::class);
        $mail = $current->mail;

        if ($mail === null) {
            return;
        }

        $message = $event->message;
        $to = collect(Recipients::to($message));

        $mail->fill([
            'recipient_email' => $to->first()?->getAddress(),
            'recipient_name' => $to->first()?->getName() ?: null,
            'cc' => Recipients::toArray(Recipients::cc($message)),
            'bcc' => Recipients::toArray(Recipients::bcc($message)),
            'subject' => $current->sensitive->mask($message->getSubject()),
        ]);

        if ($current->logged && $to->count() !== 1) {
            $mail->save();

            throw new LogicException('A notification email must have exactly one To recipient. Put the other people in CC or BCC.');
        }

        if ($current->logged) {
            $mail->html_body = $current->sensitive->mask($message->getHtmlBody());
            $mail->text_body = $current->sensitive->mask($message->getTextBody());
            $this->copyAttachments($mail, $message);
        }

        $mail->save();

        if ($mail->tracked) {
            TrackMail::make()->execute($mail, $message, $current->sensitive);
        }
    }

    public function handleMessageSent(MessageSent $event): void
    {
        $message = $event->sent->getOriginalMessage();

        if (! $message instanceof Email) {
            return;
        }

        $interceptedTo = Recipients::intercepted($message) ? $message->getTo()[0]->getAddress() : null;
        $mail = resolve(CurrentMail::class)->mail;

        $recipients = collect(Recipients::to($message) ?: [null]);

        if ($mail !== null) {
            $mail->markAsSent($event->sent->getMessageId(), $interceptedTo);
            $recipients = $recipients->skip(1);
        }

        $recipients->each(fn (?Address $recipient) => $this->recordPlainEmail($message, $recipient, $event->sent->getMessageId(), $interceptedTo));
    }

    public function handleNotificationSent(NotificationSent $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $current = resolve(CurrentMail::class);

        if ($event->response === null && $current->mail?->status === MailStatus::Sending) {
            $current->mail->fail($this->unaddressed($event)
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

        $current = resolve(CurrentMail::class);
        $exception = $event->data['exception'] ?? null;

        if (! $exception instanceof Throwable) {
            $current->end();

            return;
        }

        // A queue job tries again, and its failed() hook marks the row as Failed after the last try.
        // A LogicException is a mistake in the code, so another try cannot succeed.
        if ($current->job !== null && $event->notification instanceof HasMailLog && ! $exception instanceof LogicException) {
            $current->mail?->recordError($exception);
        } else {
            $current->mail?->markAsFailed($exception);
        }

        if ($exception instanceof LogicException) {
            $current->job?->fail($exception);
        }

        $current->end();
    }

    public function handleNotificationSkipped(NotificationSkipped $event): void
    {
        $current = resolve(CurrentMail::class);
        $mail = $current->mail;

        if ($event->channel !== 'mail' || $mail === null || $mail->notification_id !== $event->notification->id) {
            return;
        }

        $mail->fail(['type' => 'dropped', 'code' => 0, 'message' => 'A notification listener stopped the email before it was sent.']);
        $current->end();
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        if ($this->sendsMail($event->job)) {
            resolve(CurrentMail::class)->job = $event->job;
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
        return in_array(data_get($job->payload(), 'data.commandName'), [SendQueuedNotifications::class, ResendMail::class], true);
    }

    private function forgetJob(Job $job): void
    {
        $current = resolve(CurrentMail::class);

        if ($current->job === $job) {
            $current->job = null;
        }
    }

    private function redirected(Notification $notification): bool
    {
        if (! $notification instanceof HasMailLog || $notification->resendOfId === null || $notification->resendTo === null) {
            return false;
        }

        $original = Mail::query()->whereKey($notification->resendOfId)->value('recipient_email');

        return ! is_string($original) || strcasecmp($original, $notification->resendTo) !== 0;
    }

    private function unaddressed(NotificationSent $event): bool
    {
        $notifiable = $event->notifiable;

        return is_object($notifiable) && method_exists($notifiable, 'routeNotificationFor') && blank($notifiable->routeNotificationFor('mail', $event->notification));
    }

    private function recordPreparer(Mail $mail): void
    {
        if (auth(config('actor.guard'))->check()) {
            return;
        }

        $user = User::withTrashed()->whereKey(context()->get('user_id'))->first();

        if ($user !== null) {
            Actor::act($mail, Act::Prepare, $user);
        }
    }

    private function recordPlainEmail(Email $message, ?Address $recipient, ?string $messageId, ?string $interceptedTo): void
    {
        $mail = Mail::query()->create([
            'recipient_email' => $recipient?->getAddress(),
            'recipient_name' => $recipient?->getName() ?: null,
            'cc' => Recipients::toArray(Recipients::cc($message)),
            'bcc' => Recipients::toArray(Recipients::bcc($message)),
            'subject' => $message->getSubject(),
            'message_id' => $messageId,
            'intercepted_to' => $interceptedTo,
            'status' => MailStatus::Sent,
            'sent_at' => now(),
        ]);

        $this->recordPreparer($mail);
    }

    private function copyAttachments(Mail $mail, Email $message): void
    {
        $mail->clearMediaCollection(MediaCollection::Attachments);

        collect($message->getAttachments())
            ->reject(fn (DataPart $attachment): bool => $attachment->getDisposition() === 'inline')
            ->each(fn (DataPart $attachment): mixed => $mail
                ->addMediaFromString($attachment->getBody())
                ->usingFileName($attachment->getFilename() ?? 'attachment')
                ->toMediaCollection(MediaCollection::Attachments->value));
    }
}
