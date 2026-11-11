<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Mail\Enums\MailStatus;
use App\Domain\Shared\Mail\Models\Mail;
use App\Domain\User\Models\User;

class MailPolicy extends Policy
{
    protected static string $prefix = 'system.logs';

    protected static ?string $segment = 'mail';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view');
    }

    public function view(User $user, Mail $mail): bool
    {
        return $this->hasPermission($user, 'view');
    }

    public function resend(User $user, Mail $mail): bool
    {
        return ($mail->status !== MailStatus::Sending || $mail->stuck) && $mail->resendable && $this->hasPermission($user, 'resend');
    }
}
