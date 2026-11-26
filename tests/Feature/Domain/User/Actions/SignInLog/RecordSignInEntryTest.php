<?php

declare(strict_types=1);

use App\Domain\User\Actions\SignInLog\RecordSignInEntry;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Enums\SignInMethod;
use App\Domain\User\Enums\SignInResult;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;

test('recording a sign-in writes one entry with the user, the method, the impersonator and where the request came from', function (): void {
    $user = User::factory()->create();
    $impersonator = User::factory()->create();
    context()->add(['trace_id' => 'trace-123', 'impersonator_id' => $impersonator->id]);
    request()->attributes->add(['ip_address' => '192.0.2.10', 'user_agent' => 'Firefox/140.0']);

    $entry = RecordSignInEntry::make()->execute(
        event: SignInEvent::SignIn,
        result: SignInResult::SignedIn,
        user: $user,
        method: SignInMethod::Passkey,
        twoFactor: true,
    );

    expect($entry->fresh()?->only('event', 'result', 'method', 'two_factor', 'user_id', 'impersonator_id', 'ip_address', 'user_agent', 'trace_id'))->toBe([
        'event' => SignInEvent::SignIn,
        'result' => SignInResult::SignedIn,
        'method' => SignInMethod::Passkey,
        'two_factor' => true,
        'user_id' => $user->id,
        'impersonator_id' => $impersonator->id,
        'ip_address' => '192.0.2.10',
        'user_agent' => 'Firefox/140.0',
        'trace_id' => 'trace-123',
    ]);
});

test('a failed sign-in with no matching user is recorded without a user', function (): void {
    request()->attributes->add(['ip_address' => '192.0.2.10']);

    $entry = RecordSignInEntry::make()->execute(SignInEvent::FailedSignIn, SignInResult::NoMatchingUser, method: SignInMethod::Password);

    expect($entry->fresh())
        ->event->toBe(SignInEvent::FailedSignIn)
        ->result->toBe(SignInResult::NoMatchingUser)
        ->user_id->toBeNull()
        ->ip_address->toBe('192.0.2.10');
});

test('an entry outside a request and outside an impersonation is recorded without where it came from', function (): void {
    $entry = RecordSignInEntry::make()->execute(SignInEvent::SignOut, user: User::factory()->create());

    expect($entry->fresh())
        ->result->toBeNull()
        ->method->toBeNull()
        ->two_factor->toBeFalse()
        ->impersonator_id->toBeNull()
        ->ip_address->toBeNull()
        ->user_agent->toBeNull()
        ->trace_id->toBeNull();
});

test('an entry about a session takes the user, the impersonator and the place of that session, not of the request', function (): void {
    $impersonator = User::factory()->create();
    $session = UserSession::factory()->impersonatedBy($impersonator)->create(['ip_address' => '192.0.2.10', 'user_agent' => 'Firefox/140.0']);
    $endedBy = User::factory()->create();
    context()->add(['trace_id' => 'trace-123', 'impersonator_id' => $endedBy->id]);
    request()->attributes->add(['ip_address' => '198.51.100.1', 'user_agent' => 'Chrome/140.0']);

    $entry = RecordSignInEntry::make()->execute(SignInEvent::SessionEnded, user: $endedBy, session: $session, endedBy: $endedBy);

    expect($entry->fresh()?->only('event', 'user_id', 'impersonator_id', 'ended_by_id', 'ip_address', 'user_agent', 'trace_id'))->toBe([
        'event' => SignInEvent::SessionEnded,
        'user_id' => $session->user_id,
        'impersonator_id' => $impersonator->id,
        'ended_by_id' => $endedBy->id,
        'ip_address' => '192.0.2.10',
        'user_agent' => 'Firefox/140.0',
        'trace_id' => 'trace-123',
    ]);
});
