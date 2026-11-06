<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
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

        Impersonation::end();

        if (! $impersonator instanceof User) {
            auth()->guard('web')->logout();

            session()->invalidate();
            session()->regenerateToken();

            return null;
        }

        auth()->guard('web')->login($impersonator);

        session()->regenerate();
        session()->forget(['auth.password_confirmed_at', 'url.intended']);

        return $startUrl;
    }
}
