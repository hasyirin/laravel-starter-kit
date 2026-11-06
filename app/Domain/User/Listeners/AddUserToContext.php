<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Events\Authenticated;

class AddUserToContext
{
    public function handle(Authenticated $event): void
    {
        context()->add('user_id', $event->user->getAuthIdentifier());

        if (Impersonation::active()) {
            context()->add('impersonator_id', Impersonation::impersonatorId());
        }
    }
}
