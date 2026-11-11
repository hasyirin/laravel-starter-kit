<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Listeners;

use App\Domain\Shared\Mail\Actions\TrackMail;
use App\Domain\Shared\Mail\Support\Recipients;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\Shared\Message\Enums\MessageStatus;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\Shared\Message\Support\CurrentMessage;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use LogicException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class RecordMail
{
    public function handleMessageSending(MessageSending $event): void
    {
        $current = resolve(CurrentMessage::class);
        $message = $current->forChannel('mail');

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
        $message = resolve(CurrentMessage::class)->forChannel('mail');

        $recipients = collect(Recipients::to($email) ?: [null]);

        if ($message !== null) {
            $message->fill(['intercepted_to' => $interceptedTo])->markAsSent($event->sent->getMessageId());
            $recipients = $recipients->skip(1);
        }

        $recipients->each(fn (?Address $recipient) => $this->recordPlainEmail($email, $recipient, $event->sent->getMessageId(), $interceptedTo));
    }

    private function recordPlainEmail(Email $email, ?Address $recipient, ?string $providerId, ?string $interceptedTo): void
    {
        Message::query()->create([
            'channel' => 'mail',
            'recipient_address' => $recipient?->getAddress(),
            'recipient_name' => $recipient?->getName() ?: null,
            'cc' => Recipients::toArray(Recipients::cc($email)),
            'bcc' => Recipients::toArray(Recipients::bcc($email)),
            'subject' => $email->getSubject(),
            'provider_id' => $providerId,
            'intercepted_to' => $interceptedTo,
            'status' => MessageStatus::Sent,
            'sent_at' => now(),
        ])->recordPreparerFromContext();
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
