<?php

declare(strict_types=1);

use App\Domain\User\Actions\SignInLog\PruneSignInLog;
use App\Domain\User\Jobs\ApplySignInLogRetention;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

test('the job prunes the entries older than the retention days', function (): void {
    config(['system.sign_in_log.retention_days' => '30']);
    $this->travelTo(CarbonImmutable::parse('2026-06-01 00:00:00'));
    $this->mock(PruneSignInLog::class)->shouldReceive('execute')->once()->withArgs(fn (CarbonImmutable $before, int $limit): bool => $before->equalTo(CarbonImmutable::parse('2026-05-02 00:00:00')) && $limit === ApplySignInLogRetention::LIMIT)->andReturn(0);

    new ApplySignInLogRetention()->handle();
});

test('the job keeps every entry when no retention is set', function (?string $days): void {
    config(['system.sign_in_log.retention_days' => $days]);
    $this->mock(PruneSignInLog::class)->shouldNotReceive('execute');

    new ApplySignInLogRetention()->handle();
})->with(['unset' => [null], 'empty' => ['']]);

test('a run that reaches the limit queues the next run with the same cutoff', function (): void {
    Queue::fake([ApplySignInLogRetention::class]);
    config(['system.sign_in_log.retention_days' => '30']);
    $before = CarbonImmutable::parse('2026-05-02 00:00:00');
    $this->mock(PruneSignInLog::class)->shouldReceive('execute')->once()->andReturn(ApplySignInLogRetention::LIMIT);

    new ApplySignInLogRetention($before)->handle();

    Queue::assertPushed(ApplySignInLogRetention::class, fn (ApplySignInLogRetention $job): bool => $job->before?->equalTo($before) === true);
});

test('a retention below one day or not a whole number keeps every entry and warns', function (string $days): void {
    config(['system.sign_in_log.retention_days' => $days]);
    $this->mock(PruneSignInLog::class)->shouldNotReceive('execute');
    Log::spy();

    new ApplySignInLogRetention()->handle();

    Log::shouldHaveReceived('warning')->once()->with('SIGN_IN_LOG_RETENTION_DAYS must be a whole number of 1 or more. The sign-in log keeps every entry until it is.');
})->with(['zero' => ['0'], 'not a number' => ['30abc']]);

test('the job runs on the sign-in log queue connection', function (): void {
    config(['system.sign_in_log.queue_connection' => 'redis-audit']);

    expect(new ApplySignInLogRetention()->connection)->toBe('redis-audit');
});
