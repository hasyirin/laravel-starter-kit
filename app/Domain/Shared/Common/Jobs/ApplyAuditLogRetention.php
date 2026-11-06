<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Jobs;

use App\Domain\Shared\Common\Actions\PruneAuditLog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplyAuditLogRetention implements ShouldQueue
{
    use Queueable;

    public const int LIMIT = 50_000;

    public function __construct(public ?CarbonImmutable $before = null)
    {
        $this->onConnection(config()->string('system.audit.queue_connection'));
    }

    public function handle(): void
    {
        $days = config('system.audit.retention_days');

        if ($days === null || $days === '') {
            return;
        }

        $days = filter_var($days, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($days === false) {
            logger()->warning('AUDIT_RETENTION_DAYS must be a whole number of 1 or more. The audit log keeps every entry until it is.');

            return;
        }

        $before = $this->before ?? CarbonImmutable::now()->subDays($days);

        if (PruneAuditLog::make()->execute($before, self::LIMIT) === self::LIMIT) {
            dispatch(new self($before));
        }
    }
}
