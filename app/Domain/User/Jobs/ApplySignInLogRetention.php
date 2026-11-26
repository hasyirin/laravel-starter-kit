<?php

declare(strict_types=1);

namespace App\Domain\User\Jobs;

use App\Domain\User\Actions\SignInLog\PruneSignInLog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplySignInLogRetention implements ShouldQueue
{
    use Queueable;

    public const int LIMIT = 5_000;

    public function __construct(public ?CarbonImmutable $before = null)
    {
        $this->onConnection(config()->string('system.sign_in_log.queue_connection'));
    }

    public function handle(): void
    {
        $days = config('system.sign_in_log.retention_days');

        if (blank($days)) {
            return;
        }

        $days = filter_var($days, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($days === false) {
            logger()->warning('SIGN_IN_LOG_RETENTION_DAYS must be a whole number of 1 or more. The sign-in log keeps every entry until it is.');

            return;
        }

        $before = $this->before ?? CarbonImmutable::now()->subDays($days);

        if (PruneSignInLog::make()->execute($before, self::LIMIT) === self::LIMIT) {
            dispatch(new self($before));
        }
    }
}
