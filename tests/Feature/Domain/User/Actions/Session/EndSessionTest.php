<?php

declare(strict_types=1);

use App\Domain\User\Actions\Session\EndSession;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Illuminate\Support\Facades\DB;

test('ending a session destroys its id through the session handler, deletes the row and writes an entry with who ended it', function (): void {
    session()->getHandler()->write('ended-session-id', 'payload');
    $impersonator = User::factory()->create();
    $session = UserSession::factory()->impersonatedBy($impersonator)->create([
        'session_id' => 'ended-session-id',
        'ip_address' => '192.0.2.10',
        'user_agent' => 'Firefox/140.0',
    ]);
    $staff = User::factory()->create();
    context()->add('trace_id', 'trace-123');
    request()->attributes->add(['ip_address' => '198.51.100.1', 'user_agent' => 'Chrome/140.0']);

    EndSession::make()->execute($session, $staff);

    expect(session()->getHandler()->read('ended-session-id'))->toBe('')
        ->and(UserSession::withTrashed()->count())->toBe(0)
        ->and(SignInEntry::query()->sole()->only('event', 'result', 'user_id', 'impersonator_id', 'ended_by_id', 'ip_address', 'user_agent', 'trace_id'))->toBe([
            'event' => SignInEvent::SessionEnded,
            'result' => null,
            'user_id' => $session->user_id,
            'impersonator_id' => $impersonator->id,
            'ended_by_id' => $staff->id,
            'ip_address' => '192.0.2.10',
            'user_agent' => 'Firefox/140.0',
            'trace_id' => 'trace-123',
        ]);
});

test('an ended session goes to the login page at its next request', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    EndSession::make()->execute(UserSession::query()->sole(), User::factory()->create());

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('a rolled back end keeps the session and its row', function (): void {
    session()->getHandler()->write('kept-session-id', 'payload');
    $session = UserSession::factory()->create(['session_id' => 'kept-session-id']);

    expect(fn () => DB::transaction(function () use ($session): void {
        EndSession::make()->execute($session, null);

        throw new RuntimeException('A later step failed.');
    }))->toThrow(RuntimeException::class);

    expect(session()->getHandler()->read('kept-session-id'))->toBe('payload')
        ->and(UserSession::query()->whereKey($session->id)->exists())->toBeTrue()
        ->and(SignInEntry::query()->count())->toBe(0);
});
