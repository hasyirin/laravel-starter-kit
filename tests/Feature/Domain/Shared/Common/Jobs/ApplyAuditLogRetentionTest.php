<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Actions\PruneAuditLog;
use App\Domain\Shared\Common\Jobs\ApplyAuditLogRetention;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

test('the job prunes the entries older than the retention days', function (): void {
    config(['system.audit.retention_days' => '30']);
    $this->travelTo(CarbonImmutable::parse('2026-06-01 00:00:00'));
    $action = $this->mock(PruneAuditLog::class);
    $action->shouldReceive('execute')->once()->withArgs(fn (CarbonImmutable $before, int $limit): bool => $before->equalTo(CarbonImmutable::parse('2026-05-02 00:00:00')) && $limit === ApplyAuditLogRetention::LIMIT)->andReturn(0);

    new ApplyAuditLogRetention()->handle();
});

test('the job keeps every entry when no retention is set', function (?string $days): void {
    config(['system.audit.retention_days' => $days]);
    $this->mock(PruneAuditLog::class)->shouldNotReceive('execute');

    new ApplyAuditLogRetention()->handle();
})->with(['unset' => [null], 'empty' => ['']]);

test('a run that reaches the limit queues the next run with the same cutoff', function (): void {
    Queue::fake();
    config(['system.audit.retention_days' => '30']);
    $before = CarbonImmutable::parse('2026-05-02 00:00:00');
    $this->mock(PruneAuditLog::class)->shouldReceive('execute')->once()->andReturn(ApplyAuditLogRetention::LIMIT);

    new ApplyAuditLogRetention($before)->handle();

    Queue::assertPushed(ApplyAuditLogRetention::class, fn (ApplyAuditLogRetention $job): bool => $job->before?->equalTo($before) === true);
});

test('a run below the limit queues no next run', function (): void {
    Queue::fake();
    config(['system.audit.retention_days' => '30']);
    $this->mock(PruneAuditLog::class)->shouldReceive('execute')->once()->andReturn(ApplyAuditLogRetention::LIMIT - 1);

    new ApplyAuditLogRetention()->handle();

    Queue::assertNothingPushed();
});

test('the job runs on the audit queue connection', function (): void {
    config(['system.audit.queue_connection' => 'redis-audit']);

    expect(new ApplyAuditLogRetention()->connection)->toBe('redis-audit');
});

test('a retention below one day or not a whole number keeps every entry and warns', function (string $days): void {
    config(['system.audit.retention_days' => $days]);
    $this->mock(PruneAuditLog::class)->shouldNotReceive('execute');
    Log::spy();

    new ApplyAuditLogRetention()->handle();

    Log::shouldHaveReceived('warning')->once();
})->with(['zero' => ['0'], 'negative' => ['-1'], 'not a number' => ['30abc'], 'a fraction' => ['1.5']]);
