<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Message\Concerns\RoutesResentMessages;
use App\Domain\Shared\Message\Contracts\HasMessageLog;
use App\Domain\Shared\Message\Jobs\ResendMessage as ResendMessageJob;
use App\Domain\Shared\Message\Models\Message;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;
use LogicException;
use SensitiveParameter;

class ResendMessage
{
    use Actionable;

    public function execute(Message $message, #[SensitiveParameter] ?string $address = null): void
    {
        $notification = $message->notification;

        throw_unless($message->resendable && $notification instanceof HasMessageLog, LogicException::class, 'This message cannot be resent. A resend needs an email or an SMS that is not on its way, with a notification that can be rebuilt.');

        $address = filled($address) ? $address : (string) $message->recipient_address;

        // A new id gives the resent message its own row. It stays the same when the job tries again.
        $notification->id = (string) Str::uuid();
        $notification->resendOfId = $message->id;
        $notification->resendTo = $address;

        dispatch(new ResendMessageJob($this->notifiable($message, $address), $notification, $message->channel));
    }

    private function notifiable(Message $message, #[SensitiveParameter] string $address): object
    {
        if ($message->notifiable !== null && in_array(RoutesResentMessages::class, class_uses_recursive($message->notifiable), true)) {
            return $message->notifiable;
        }

        if ($message->channel === 'mail') {
            return new AnonymousNotifiable()->route('mail', [$address => $message->recipient_name]);
        }

        return new AnonymousNotifiable()->route('sms', $address);
    }
}
