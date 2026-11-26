<?php

declare(strict_types=1);

namespace App\Domain\User\Listeners;

use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Events\Login;

class RecordSignIn
{
    public function handle(Login $event): void
    {
        if ($event->guard !== config()->string('fortify.guard') || ! $event->user instanceof User) {
            return;
        }

        if (Impersonation::becoming()) {
            return;
        }

        $route = request()->route()?->getName();

        RecordSignInEntry::make()->execute(
            SignInEvent::SignIn,
            SignInResult::SignedIn,
            $event->user,
            SignInMethod::fromRoute($route),
            twoFactor: $route === 'two-factor.login.store',
        );
    }
}
