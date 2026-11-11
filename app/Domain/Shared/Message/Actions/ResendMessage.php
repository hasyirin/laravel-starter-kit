<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Mail\Concerns\RoutesResentMail;
use App\Domain\Shared\Message\Contracts\HasMessageLog;
use App\Domain\Shared\Message\Jobs\ResendMessage as ResendMessageJob;
use App\Domain\Shared\Message\Models\Message;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;
use LogicException;

class ResendMessage
{
    use Actionable;

    public function execute(Message $message, ?string $email = null): void
    {
        $notification = $message->notification;

        throw_unless($notification instanceof HasMessageLog, LogicException::class, 'This email cannot be resent, because its notification cannot be rebuilt.');

        $address = filled($email) ? $email : (string) $message->recipient_address;

        // A new id gives the resent email its own row. It stays the same when the job tries again.
        $notification->id = (string) Str::uuid();
        $notification->resendOfId = $message->id;
        $notification->resendTo = $address;

        $notifiable = $message->notifiable !== null && in_array(RoutesResentMail::class, class_uses_recursive($message->notifiable), true)
            ? $message->notifiable
            : new AnonymousNotifiable()->route('mail', [$address => $message->recipient_name]);

        dispatch(new ResendMessageJob($notifiable, $notification));
    }
}
