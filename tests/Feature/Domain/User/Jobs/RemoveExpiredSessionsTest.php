<?php

declare(strict_types=1);

use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Jobs\RemoveExpiredSessions;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\UserSession;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    config(['session.lifetime' => 43200, 'system.sessions.max_days' => 90]);
});

test('the job removes idle sessions and sessions past the maximum, with one expired entry each, and keeps live ones', function (): void {
    $idle = UserSession::factory()->create(['last_activity_at' => now()->subMinutes(43200 + 2)]);
    $pastMaximum = UserSession::factory()->create(['signed_in_at' => now()->subDays(90)->subMinute()]);
    $live = UserSession::factory()->create([
        'last_activity_at' => now()->subMinutes(43200),
        'signed_in_at' => now()->subDays(89),
    ]);

    new RemoveExpiredSessions()->handle();

    expect(UserSession::query()->pluck('id')->all())->toBe([$live->id])
        ->and(SignInEntry::query()->where('event', SignInEvent::SessionExpired)->pluck('user_id')->sort()->values()->all())
        ->toBe(collect([$idle->user_id, $pastMaximum->user_id])->sort()->values()->all())
        ->and(SignInEntry::query()->count())->toBe(2);
});

test('an empty maximum means no maximum', function (): void {
    config(['system.sessions.max_days' => '']);
    Log::spy();
    $old = UserSession::factory()->create(['signed_in_at' => now()->subDays(400)]);

    new RemoveExpiredSessions()->handle();

    expect(UserSession::query()->pluck('id')->all())->toBe([$old->id]);
    Log::shouldNotHaveReceived('warning');
});

test('a maximum below one day or not a whole number means no maximum and warns', function (string $maxDays): void {
    config(['system.sessions.max_days' => $maxDays]);
    Log::spy();
    $old = UserSession::factory()->create(['signed_in_at' => now()->subDays(400)]);

    new RemoveExpiredSessions()->handle();

    expect(UserSession::query()->pluck('id')->all())->toBe([$old->id]);
    Log::shouldHaveReceived('warning')->once()->with('SESSION_MAX_DAYS must be a whole number of 1 or more. Sessions have no maximum until it is.');
})->with(['zero' => ['0'], 'not a number' => ['90abc']]);
