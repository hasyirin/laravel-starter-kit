<?php

declare(strict_types=1);

use App\Domain\User\Actions\Session\ExpireSession;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\UserSession;
use Illuminate\Support\Facades\DB;

test('expiring a session destroys its id through the session handler, deletes the row and writes an expired entry', function (): void {
    session()->getHandler()->write('expired-session-id', 'payload');
    $session = UserSession::factory()->create(['session_id' => 'expired-session-id', 'ip_address' => '192.0.2.10']);

    ExpireSession::make()->execute($session);

    expect(session()->getHandler()->read('expired-session-id'))->toBe('')
        ->and(UserSession::withTrashed()->count())->toBe(0)
        ->and(SignInEntry::query()->sole()->only('event', 'user_id', 'ended_by_id', 'ip_address'))->toBe([
            'event' => SignInEvent::SessionExpired,
            'user_id' => $session->user_id,
            'ended_by_id' => null,
            'ip_address' => '192.0.2.10',
        ]);
});

test('a rolled back expiry keeps the session and its row', function (): void {
    session()->getHandler()->write('kept-session-id', 'payload');
    $session = UserSession::factory()->create(['session_id' => 'kept-session-id']);

    expect(fn () => DB::transaction(function () use ($session): void {
        ExpireSession::make()->execute($session);

        throw new RuntimeException('A later step failed.');
    }))->toThrow(RuntimeException::class);

    expect(session()->getHandler()->read('kept-session-id'))->toBe('payload')
        ->and(UserSession::query()->whereKey($session->id)->exists())->toBeTrue()
        ->and(SignInEntry::query()->count())->toBe(0);
});
