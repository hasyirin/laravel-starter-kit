<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use Illuminate\Auth\Events\Authenticated;

class AddUserToContext
{
    public function handle(Authenticated $event): void
    {
        context()->add('user_id', $event->user->getAuthIdentifier());
    }
}
