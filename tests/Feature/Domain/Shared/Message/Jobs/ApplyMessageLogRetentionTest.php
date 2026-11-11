<?php

declare(strict_types=1);

use App\Domain\Shared\Message\Actions\PruneMessageLog;
use App\Domain\Shared\Message\Jobs\ApplyMessageLogRetention;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

test('the job prunes the emails older than the retention days', function (): void {
    config(['system.message_log.retention_days' => '30']);
    $this->travelTo(CarbonImmutable::parse('2026-06-01 00:00:00'));
    $this->mock(PruneMessageLog::class)->shouldReceive('execute')->once()->withArgs(fn (CarbonImmutable $before, int $limit): bool => $before->equalTo(CarbonImmutable::parse('2026-05-02 00:00:00')) && $limit === ApplyMessageLogRetention::LIMIT)->andReturn(0);

    new ApplyMessageLogRetention()->handle();
});

test('the job keeps every email when no retention is set', function (?string $days): void {
    config(['system.message_log.retention_days' => $days]);
    $this->mock(PruneMessageLog::class)->shouldNotReceive('execute');

    new ApplyMessageLogRetention()->handle();
})->with(['unset' => [null], 'empty' => ['']]);

test('a run that reaches the limit queues the next run with the same cutoff', function (): void {
    Queue::fake([ApplyMessageLogRetention::class]);
    config(['system.message_log.retention_days' => '30']);
    $before = CarbonImmutable::parse('2026-05-02 00:00:00');
    $this->mock(PruneMessageLog::class)->shouldReceive('execute')->once()->andReturn(ApplyMessageLogRetention::LIMIT);

    new ApplyMessageLogRetention($before)->handle();

    Queue::assertPushed(ApplyMessageLogRetention::class, fn (ApplyMessageLogRetention $job): bool => $job->before?->equalTo($before) === true);
});

test('a retention below one day or not a whole number keeps every email and warns', function (string $days): void {
    config(['system.message_log.retention_days' => $days]);
    $this->mock(PruneMessageLog::class)->shouldNotReceive('execute');
    Log::spy();

    new ApplyMessageLogRetention()->handle();

    Log::shouldHaveReceived('warning')->once()->with('MESSAGE_RETENTION_DAYS must be a whole number of 1 or more. The message log keeps every email until it is.');
})->with(['zero' => ['0'], 'not a number' => ['30abc']]);

test('the job runs on the message log queue connection', function (): void {
    config(['system.message_log.queue_connection' => 'redis-audit']);

    expect(new ApplyMessageLogRetention()->connection)->toBe('redis-audit');
});
