<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Listeners;

use App\Domain\Shared\Mail\Support\Recipients;
use App\Domain\Shared\Message\Support\Intercept;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class InterceptMail
{
    public function handle(MessageSending $event): ?false
    {
        if (! $this->enabled()) {
            return null;
        }

        $message = $event->message;
        $recipients = collect([...$message->getTo(), ...$message->getCc(), ...$message->getBcc()])
            ->map(fn (Address $address): string => $address->getAddress())
            ->join(', ');

        $address = $this->address();

        if ($address === null) {
            logger()->info('Mail intercept dropped an email because it has no address.', ['to' => $recipients, 'subject' => $message->getSubject()]);

            return false;
        }

        $this->keepRecipients($message);
        $message->getHeaders()->remove('Cc');
        $message->getHeaders()->remove('Bcc');
        $message->to($address);

        return null;
    }

    private function keepRecipients(Email $message): void
    {
        collect(Recipients::FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => match ($field) {
                'To' => $message->getTo(),
                'Cc' => $message->getCc(),
                default => $message->getBcc(),
            }])
            ->filter()
            ->each(fn (array $addresses, string $field): mixed => $message->getHeaders()->addMailboxListHeader(Recipients::header($field), $addresses));
    }

    private function enabled(): bool
    {
        return Intercept::enabled('mail.intercept.enabled');
    }

    private function address(): ?string
    {
        return Intercept::user()->email ?? (config()->string('mail.intercept.address') ?: null);
    }
}
