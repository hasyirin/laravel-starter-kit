<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

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

        auth()->guard('web')->login($user);

        session()->regenerate();
        session()->forget(['auth.password_confirmed_at', 'url.intended']);

        Impersonation::begin($impersonator, $startUrl);
    }
}
