<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Notifications;

use App\Domain\Shared\Mail\Concerns\InteractsWithMailLog;
use App\Domain\Shared\Mail\Contracts\HasMailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

abstract class Notification extends \Illuminate\Notifications\Notification implements HasMailLog, ShouldQueue, ShouldQueueAfterCommit
{
    use InteractsWithMailLog;
    use Queueable;
}
