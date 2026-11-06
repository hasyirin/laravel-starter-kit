<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Jobs\ApplyAuditLogRetention;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('the scheduler runs the cleanup tasks daily', function (string $description): void {
    $event = collect(resolve(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) ($event->description ?: $event->command), $description));

    expect($event)->not->toBeNull()
        ->and($event?->expression)->toBe('0 0 * * *');
})->with([
    'the audit log retention' => [ApplyAuditLogRetention::class],
    'the failed jobs older than 7 days' => ['queue:prune-failed --hours=168'],
    'the expired password reset tokens' => ['auth:clear-resets'],
]);

test('every scheduled task runs on one server when several servers run the scheduler', function (): void {
    $events = collect(resolve(Schedule::class)->events());

    expect($events)->not->toBeEmpty()
        ->and($events->reject(fn (Event $event): bool => $event->onOneServer)->map(fn (Event $event): string => (string) ($event->description ?: $event->command))->all())->toBeEmpty();
});
