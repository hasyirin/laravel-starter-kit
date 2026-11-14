<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Impersonation;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Impersonation;
use Illuminate\Auth\Access\AuthorizationException;

class StartImpersonation
{
    use Actionable;

    /**
     * @throws AuthorizationException
     */
    public function execute(User $impersonator, User $user, string $startUrl): void
    {
        throw_unless(Impersonation::allows($impersonator, $user), AuthorizationException::class);

        // The sign-in can send a message. An intercept must then already know the impersonator.
        context()->add('impersonator_id', $impersonator->id);

        auth()->guard('web')->login($user);

        session()->regenerate();
        session()->forget(['auth.password_confirmed_at', 'url.intended']);

        Impersonation::begin($impersonator, $startUrl);
    }
}
