<?php

declare(strict_types=1);

namespace App\Domain\User\Jobs;

use App\Domain\User\Actions\Session\ExpireSession;
use App\Domain\User\Models\UserSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class RemoveExpiredSessions implements ShouldQueue
{
    use Queueable;

    private const int CHUNK_SIZE = 500;

    public function handle(): void
    {
        if (filled(config('system.sessions.max_days')) && UserSession::maxDays() === null) {
            logger()->warning('SESSION_MAX_DAYS must be a whole number of 1 or more. Sessions have no maximum until it is.');
        }

        UserSession::query()
            ->expired()
            ->chunkById(self::CHUNK_SIZE, function (Collection $sessions): void {
                $sessions->each(fn (UserSession $session) => ExpireSession::make()->execute($session));
            });
    }
}
