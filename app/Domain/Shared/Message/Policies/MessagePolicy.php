<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Policies;

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\User\Models\User;

class MessagePolicy extends Policy
{
    protected static string $prefix = 'system.logs';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view');
    }

    public function view(User $user, Message $message): bool
    {
        return $this->hasPermission($user, 'view');
    }

    public function resend(User $user, Message $message): bool
    {
        return $message->resendable && $this->hasPermission($user, 'resend');
    }
}
