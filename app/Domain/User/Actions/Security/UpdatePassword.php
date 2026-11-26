<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Security;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Actions\Session\EndAllSessions;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use App\Domain\User\Support\Impersonation;
use SensitiveParameter;

class UpdatePassword
{
    use Actionable;

    public function execute(User $user, #[SensitiveParameter] string $password): void
    {
        $user->getConnection()->transaction(function () use ($user, $password): void {
            $user->update([
                'password' => $password,
            ]);

            EndAllSessions::make()->execute($user, endedBy: Impersonation::personAtBrowser(), except: UserSession::current());
        });
    }
}
