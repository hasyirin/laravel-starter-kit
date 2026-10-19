<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Listeners;

use App\Domain\User\Models\User;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;

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

        $message->getHeaders()->remove('Cc');
        $message->getHeaders()->remove('Bcc');
        $message->to($address);
        $message->getHeaders()->addTextHeader('X-Intercepted-To', $recipients);

        return null;
    }

    private function enabled(): bool
    {
        return config()->boolean('mail.intercept.enabled') && ! app()->isProduction();
    }

    private function address(): ?string
    {
        /** @var User|null $user */
        $user = auth()->user() ?? User::query()->find(context()->get('user_id'));

        return $user->email ?? (config()->string('mail.intercept.address') ?: null);
    }
}
