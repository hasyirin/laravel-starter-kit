<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Session;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\UserSession;

class ExpireSession
{
    use Actionable;

    public function execute(UserSession $session): void
    {
        $session->getConnection()->transaction(function () use ($session): void {
            RecordSignInEntry::make()->execute(SignInEvent::SessionExpired, session: $session);

            $session->forceDelete();

            $sessionId = $session->session_id;
            $session->getConnection()->afterCommit(fn (): bool => session()->getHandler()->destroy($sessionId));
        });
    }
}
