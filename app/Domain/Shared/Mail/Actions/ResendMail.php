<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Mail\Concerns\RoutesResentMail;
use App\Domain\Shared\Mail\Contracts\HasMailLog;
use App\Domain\Shared\Mail\Jobs\ResendMail as ResendMailJob;
use App\Domain\Shared\Mail\Models\Mail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;
use LogicException;

class ResendMail
{
    use Actionable;

    public function execute(Mail $mail, ?string $email = null): void
    {
        $notification = $mail->notification;

        throw_unless($notification instanceof HasMailLog, LogicException::class, 'This email cannot be resent, because its notification cannot be rebuilt.');

        $address = filled($email) ? $email : (string) $mail->recipient_email;

        // A new id gives the resent email its own row. It stays the same when the job tries again.
        $notification->id = (string) Str::uuid();
        $notification->resendOfId = $mail->id;
        $notification->resendTo = $address;

        $notifiable = $mail->notifiable !== null && in_array(RoutesResentMail::class, class_uses_recursive($mail->notifiable), true)
            ? $mail->notifiable
            : new AnonymousNotifiable()->route('mail', [$address => $mail->recipient_name]);

        dispatch(new ResendMailJob($notifiable, $notification));
    }
}
