<?php

declare(strict_types=1);

use App\Domain\Shared\Mail\Actions\PruneMailLog;
use App\Domain\Shared\Mail\Jobs\ApplyMailLogRetention;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

test('the job prunes the emails older than the retention days', function (): void {
    config(['system.mail_log.retention_days' => '30']);
    $this->travelTo(CarbonImmutable::parse('2026-06-01 00:00:00'));
    $this->mock(PruneMailLog::class)->shouldReceive('execute')->once()->withArgs(fn (CarbonImmutable $before, int $limit): bool => $before->equalTo(CarbonImmutable::parse('2026-05-02 00:00:00')) && $limit === ApplyMailLogRetention::LIMIT)->andReturn(0);

    new ApplyMailLogRetention()->handle();
});

test('the job keeps every email when no retention is set', function (?string $days): void {
    config(['system.mail_log.retention_days' => $days]);
    $this->mock(PruneMailLog::class)->shouldNotReceive('execute');

    new ApplyMailLogRetention()->handle();
})->with(['unset' => [null], 'empty' => ['']]);

test('a run that reaches the limit queues the next run with the same cutoff', function (): void {
    Queue::fake([ApplyMailLogRetention::class]);
    config(['system.mail_log.retention_days' => '30']);
    $before = CarbonImmutable::parse('2026-05-02 00:00:00');
    $this->mock(PruneMailLog::class)->shouldReceive('execute')->once()->andReturn(ApplyMailLogRetention::LIMIT);

    new ApplyMailLogRetention($before)->handle();

    Queue::assertPushed(ApplyMailLogRetention::class, fn (ApplyMailLogRetention $job): bool => $job->before?->equalTo($before) === true);
});

test('a retention below one day or not a whole number keeps every email and warns', function (string $days): void {
    config(['system.mail_log.retention_days' => $days]);
    $this->mock(PruneMailLog::class)->shouldNotReceive('execute');
    Log::spy();

    new ApplyMailLogRetention()->handle();

    Log::shouldHaveReceived('warning')->once()->with('MAIL_RETENTION_DAYS must be a whole number of 1 or more. The mail log keeps every email until it is.');
})->with(['zero' => ['0'], 'not a number' => ['30abc']]);

test('the job runs on the mail log queue connection', function (): void {
    config(['system.mail_log.queue_connection' => 'redis-audit']);

    expect(new ApplyMailLogRetention()->connection)->toBe('redis-audit');
});
