<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\User;
use Illuminate\Auth\Events\Failed;

class RecordFailedSignIn
{
    public function handle(Failed $event): void
    {
        if ($event->guard !== config()->string('fortify.guard')) {
            return;
        }

        if (! $event->user instanceof User) {
            RecordSignInEntry::make()->execute(SignInEvent::FailedSignIn, SignInResult::NoMatchingUser, method: SignInMethod::Password);

            return;
        }

        RecordSignInEntry::make()->execute(SignInEvent::FailedSignIn, SignInResult::WrongPassword, $event->user, SignInMethod::Password);
    }
}
