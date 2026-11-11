<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use App\Domain\Shared\Mail\Concerns\InteractsWithMailLog;
use App\Domain\Shared\Mail\Contracts\HasMailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class VerifyEmail extends \Illuminate\Auth\Notifications\VerifyEmail implements HasMailLog, ShouldQueue, ShouldQueueAfterCommit
{
    use InteractsWithMailLog;
    use Queueable;

    private ?string $url = null;

    public function tracked(object $notifiable): bool
    {
        return false;
    }

    /**
     * @return list<string>
     */
    public function sensitive(object $notifiable): array
    {
        return [$this->verificationUrl($notifiable)];
    }

    /**
     * @param  mixed  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        // The signed URL holds an expiry time, so build it once. Then the masked URL matches the URL in the email.
        return $this->url ??= parent::verificationUrl($notifiable);
    }
}
