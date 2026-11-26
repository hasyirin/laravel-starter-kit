<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Session;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;

class EndSession
{
    use Actionable;

    public function execute(UserSession $session, ?User $endedBy): void
    {
        $session->getConnection()->transaction(function () use ($session, $endedBy): void {
            RecordSignInEntry::make()->execute(SignInEvent::SessionEnded, session: $session, endedBy: $endedBy);

            $session->forceDelete();

            $sessionId = $session->session_id;
            $session->getConnection()->afterCommit(fn (): bool => session()->getHandler()->destroy($sessionId));
        });
    }
}
