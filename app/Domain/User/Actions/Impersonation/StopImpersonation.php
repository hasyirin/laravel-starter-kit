<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Impersonation;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Access\AuthorizationException;

class StopImpersonation
{
    use Actionable;

    /**
     * @throws AuthorizationException
     */
    public function execute(): ?string
    {
        throw_unless(Impersonation::active(), AuthorizationException::class);

        $impersonator = Impersonation::impersonator();
        $startUrl = Impersonation::startUrl();

        context()->add('impersonator_id', Impersonation::impersonatorId());

        RecordSignInEntry::make()->execute(SignInEvent::ImpersonationStopped, user: auth()->guard('web')->user());

        Impersonation::end();

        if (! $impersonator instanceof User) {
            auth()->guard('web')->logout();

            session()->invalidate();
            session()->regenerateToken();

            return null;
        }

        Impersonation::become($impersonator);

        session()->regenerate();
        session()->forget(['auth.password_confirmed_at', 'url.intended']);

        return $startUrl;
    }
}
