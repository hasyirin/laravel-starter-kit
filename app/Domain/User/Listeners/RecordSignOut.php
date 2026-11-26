<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\User;
use Illuminate\Auth\Events\Logout;

class RecordSignOut
{
    public function handle(Logout $event): void
    {
        if ($event->guard !== config()->string('fortify.guard') || ! $event->user instanceof User) {
            return;
        }

        RecordSignInEntry::make()->execute(SignInEvent::SignOut, user: $event->user);
    }
}
