<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Observers;

use App\Domain\Shared\Message\Models\Message;
use Illuminate\Support\Str;

class MessageObserver
{
    public function creating(Message $message): void
    {
        $message->tracking_token ??= Str::random(48);
    }
}
