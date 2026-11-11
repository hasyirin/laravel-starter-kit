<?php

declare(strict_types=1);

namespace App\Domain\Shared\Mail\Jobs;

use App\Domain\Shared\Mail\Actions\PruneMailLog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplyMailLogRetention implements ShouldQueue
{
    use Queueable;

    public const int LIMIT = 5_000;

    public function __construct(public ?CarbonImmutable $before = null)
    {
        $this->onConnection(config()->string('system.mail_log.queue_connection'));
    }

    public function handle(): void
    {
        $days = config('system.mail_log.retention_days');

        if ($days === null || $days === '') {
            return;
        }

        $days = filter_var($days, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($days === false) {
            logger()->warning('MAIL_RETENTION_DAYS must be a whole number of 1 or more. The mail log keeps every email until it is.');

            return;
        }

        $before = $this->before ?? CarbonImmutable::now()->subDays($days);

        if (PruneMailLog::make()->execute($before, self::LIMIT) === self::LIMIT) {
            dispatch(new self($before));
        }
    }
}
