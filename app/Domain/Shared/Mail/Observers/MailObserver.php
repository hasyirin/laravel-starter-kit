<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Observers;

use App\Domain\Shared\Mail\Models\Mail;
use Illuminate\Support\Str;

class MailObserver
{
    public function creating(Mail $mail): void
    {
        $mail->tracking_token ??= Str::random(48);
    }
}
