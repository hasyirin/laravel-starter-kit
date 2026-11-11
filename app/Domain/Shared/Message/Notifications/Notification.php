<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Notifications;

use App\Domain\Shared\Message\Concerns\InteractsWithMessageLog;
use App\Domain\Shared\Message\Contracts\HasMessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

abstract class Notification extends \Illuminate\Notifications\Notification implements HasMessageLog, ShouldQueue, ShouldQueueAfterCommit
{
    use InteractsWithMessageLog;
    use Queueable;
}
