<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Illuminate\Support\Facades\Schema;

test('sessions stay out of the audit log', function (): void {
    $session = UserSession::factory()->create();
    $session->update(['ip_address' => '192.0.2.10']);

    $session->forceDelete();

    expect(AuditEntry::query()->where('subject_type', 'user_session')->exists())->toBeFalse();
});

test('a session never shows its session id', function (): void {
    $session = UserSession::factory()->create(['session_id' => 'secret-session-id']);

    expect($session->toArray())->not->toHaveKey('session_id')
        ->and(json_encode($session))->not->toContain('secret-session-id')
        ->and(Schema::getColumnListing(new SignInEntry()->getTable()))->not->toContain('session_id');
});

test('the owner of a session is the impersonator during an impersonation, and the user otherwise', function (): void {
    $user = User::factory()->create();
    $own = UserSession::factory()->forUser($user)->create();
    $asImpersonator = UserSession::factory()->impersonatedBy($user)->create();
    UserSession::factory()->forUser($user)->impersonatedBy(User::factory()->create())->create();
    UserSession::factory()->create();

    expect(UserSession::query()->ownedBy($user)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$own->id, $asImpersonator->id])->sort()->values()->all());
});

test('a session is expired one minute after the idle limit, or after the maximum days from the sign-in', function (): void {
    config(['session.lifetime' => 60, 'system.sessions.max_days' => 90]);
    $this->freezeSecond();
    $idle = UserSession::factory()->create(['last_activity_at' => now()->subMinutes(61)->subSecond()]);
    $pastMaximum = UserSession::factory()->create(['signed_in_at' => now()->subDays(90)->subSecond()]);
    UserSession::factory()->create(['last_activity_at' => now()->subMinutes(61)]);
    UserSession::factory()->create(['signed_in_at' => now()->subDays(90)]);

    expect(UserSession::query()->expired()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$idle->id, $pastMaximum->id])->sort()->values()->all())
        ->and($pastMaximum->past_maximum)->toBeTrue()
        ->and($idle->past_maximum)->toBeFalse();
});

test('the current session is the row that the session names', function (): void {
    $session = UserSession::factory()->create();

    expect(UserSession::current())->toBeNull();

    session()->put(UserSession::SESSION_KEY, $session->id);

    expect(UserSession::current()?->is($session))->toBeTrue();
});

test('a session keeps its user and its impersonator after they are deleted', function (): void {
    $session = UserSession::factory()->impersonatedBy(User::factory()->create())->create();
    $session->user?->delete();
    $session->impersonator?->delete();

    $fresh = $session->fresh();

    expect($fresh?->user)->not->toBeNull()
        ->and($fresh?->impersonator)->not->toBeNull();
});
