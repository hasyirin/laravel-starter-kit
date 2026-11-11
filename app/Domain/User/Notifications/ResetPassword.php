<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use App\Domain\Shared\Mail\Concerns\InteractsWithMailLog;
use App\Domain\Shared\Mail\Contracts\HasMailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class ResetPassword extends \Illuminate\Auth\Notifications\ResetPassword implements HasMailLog, ShouldBeEncrypted, ShouldQueue, ShouldQueueAfterCommit
{
    use InteractsWithMailLog;
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
