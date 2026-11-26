<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\User;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

class RecordWrongTwoFactorCode
{
    public function handle(TwoFactorAuthenticationFailed $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        RecordSignInEntry::make()->execute(
            SignInEvent::FailedSignIn,
            SignInResult::WrongTwoFactorCode,
            $event->user,
            SignInMethod::Password,
            twoFactor: true,
        );
    }
}
