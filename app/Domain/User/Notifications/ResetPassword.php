<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use App\Domain\Shared\Message\Concerns\InteractsWithMessageLog;
use App\Domain\Shared\Message\Contracts\HasMessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class ResetPassword extends \Illuminate\Auth\Notifications\ResetPassword implements HasMessageLog, ShouldBeEncrypted, ShouldQueue, ShouldQueueAfterCommit
{
    use InteractsWithMessageLog;
    use Queueable;

    public function tracked(object $notifiable): bool
    {
        return false;
    }

    /**
     * @return list<string>
     */
    public function sensitive(object $notifiable): array
    {
        return [$this->token, $this->resetUrl($notifiable)];
    }
}
