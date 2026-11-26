<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\Session;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Illuminate\Database\Eloquent\Builder;

class EndAllSessions
{
    use Actionable;

    public function execute(User $user, ?User $endedBy = null, ?UserSession $except = null, bool $includeImpersonated = false): int
    {
        return $user->getConnection()->transaction(function () use ($user, $endedBy, $except, $includeImpersonated): int {
            $sessions = UserSession::query()
                ->where(fn (Builder $query): Builder => $query
                    ->ownedBy($user)
                    ->when($includeImpersonated, fn (Builder $query): Builder => $query->orWhere('user_id', $user->id)))
                ->when($except instanceof UserSession, fn (Builder $query): Builder => $query->whereKeyNot($except?->id))
                ->get();

            $sessions->each(fn (UserSession $session) => EndSession::make()->execute($session, $endedBy));

            return $sessions->count();
        });
    }
}
