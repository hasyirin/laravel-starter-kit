<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Actions\PruneApiLog;
use App\Domain\Shared\Api\Jobs\ApplyApiLogRetention;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

test('the job prunes the calls older than the retention days', function (): void {
    config(['system.api_log.retention_days' => '30']);
    $this->travelTo(CarbonImmutable::parse('2026-06-01 00:00:00'));
    $this->mock(PruneApiLog::class)->shouldReceive('execute')->once()->withArgs(fn (CarbonImmutable $before, int $limit): bool => $before->equalTo(CarbonImmutable::parse('2026-05-02 00:00:00')) && $limit === ApplyApiLogRetention::LIMIT)->andReturn(0);

    new ApplyApiLogRetention()->handle();
});

test('the job keeps every call when no retention is set', function (?string $days): void {
    config(['system.api_log.retention_days' => $days]);
    $this->mock(PruneApiLog::class)->shouldNotReceive('execute');

    new ApplyApiLogRetention()->handle();
})->with(['unset' => [null], 'empty' => ['']]);

test('a run that reaches the limit queues the next run with the same cutoff', function (): void {
    Queue::fake([ApplyApiLogRetention::class]);
    config(['system.api_log.retention_days' => '30']);
    $before = CarbonImmutable::parse('2026-05-02 00:00:00');
    $this->mock(PruneApiLog::class)->shouldReceive('execute')->once()->andReturn(ApplyApiLogRetention::LIMIT);

    new ApplyApiLogRetention($before)->handle();

    Queue::assertPushed(ApplyApiLogRetention::class, fn (ApplyApiLogRetention $job): bool => $job->before?->equalTo($before) === true);
});

test('a retention below one day or not a whole number keeps every call and warns', function (string $days): void {
    config(['system.api_log.retention_days' => $days]);
    $this->mock(PruneApiLog::class)->shouldNotReceive('execute');
    Log::spy();

    new ApplyApiLogRetention()->handle();

    Log::shouldHaveReceived('warning')->once()->with('API_LOG_RETENTION_DAYS must be a whole number of 1 or more. The API log keeps every call until it is.');
})->with(['zero' => ['0'], 'not a number' => ['30abc']]);

test('the job runs on the API log queue connection', function (): void {
    config(['system.api_log.queue_connection' => 'redis-audit']);

    expect(new ApplyApiLogRetention()->connection)->toBe('redis-audit');
});
