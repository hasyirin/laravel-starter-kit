<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Actions\Impersonation\StartImpersonation;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

test('a sign-in by password makes one session row for the signed-in user', function (): void {
    $this->freezeSecond();
    $user = User::factory()->create();

    $this->withHeader('User-Agent', 'Firefox/140.0')->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
    $session = UserSession::query()->sole();
    expect($session->only('user_id', 'impersonator_id', 'session_id', 'ip_address', 'user_agent'))->toBe([
        'user_id' => $user->id,
        'impersonator_id' => null,
        'session_id' => session()->getId(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Firefox/140.0',
    ])
        ->and($session->signed_in_at->equalTo(now()))->toBeTrue()
        ->and($session->last_activity_at->equalTo(now()))->toBeTrue()
        ->and(session(UserSession::SESSION_KEY))->toBe($session->id);
});

test('a sign-in by two-factor code makes one session row, and the pending challenge makes none', function (): void {
    $this->skipUnlessFeature(Feature::TwoFactorAuthentication);
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('two-factor.login'));

    expect(UserSession::query()->count())->toBe(0);

    $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-code-1']);

    $this->assertAuthenticatedAs($user);
    expect(UserSession::query()->sole()->user_id)->toBe($user->id);
});

test('a guest gets no session row', function (): void {
    $this->get(route('login'))->assertOk();

    expect(UserSession::query()->count())->toBe(0);
});

test('a signed-in session with no row gets one at its next request', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect(UserSession::query()->sole()->user_id)->toBe($user->id)
        ->and(session(UserSession::SESSION_KEY))->toBe(UserSession::query()->sole()->id);
});

test('a change of the session id updates the session id on the same row', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $session = UserSession::query()->sole();
    $oldId = session()->getId();

    $this->get(route('dashboard'))->assertOk();

    expect(session()->getId())->not->toBe($oldId)
        ->and(UserSession::query()->sole()->only('id', 'session_id'))->toBe(['id' => $session->id, 'session_id' => session()->getId()]);
});

test('a second request inside 60 seconds does not write the last activity, and a later one does', function (): void {
    $this->freezeSecond();
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $session = UserSession::query()->sole();

    $this->travel(59)->seconds();
    $this->withCookie(config()->string('session.cookie'), session()->getId())->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->get(route('dashboard'))->assertOk();

    expect($session->fresh()?->only('last_activity_at', 'updated_at', 'ip_address'))->toEqual($session->only('last_activity_at', 'updated_at', 'ip_address'));

    $this->travel(2)->seconds();
    $this->withCookie(config()->string('session.cookie'), session()->getId())->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])->get(route('dashboard'))->assertOk();

    expect($session->fresh())
        ->last_activity_at->equalTo(now())->toBeTrue()
        ->ip_address->toBe('192.0.2.10');
});

test('a tracked request reads its session row once', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $selects = collect();
    DB::listen(function (QueryExecuted $query) use ($selects): void {
        if (str($query->sql)->startsWith('select') && str($query->sql)->contains('"user_sessions"')) {
            $selects->push($query->sql);
        }
    });

    $this->withCookie(config()->string('session.cookie'), session()->getId())->get(route('dashboard'))->assertOk();

    expect($selects)->toHaveCount(1);
});

test('a start and a stop of an impersonation keep the same row and sign-in time, and set and clear the impersonator', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->freezeSecond();
    $impersonator = User::factory()->create();
    $user = User::factory()->create();
    grantPermissions($impersonator, 'internal.controls.users.impersonate', 'internal.controls.users.update');
    $this->actingAs($impersonator)->get(route('dashboard'))->assertOk();
    $session = UserSession::query()->sole();
    $this->travel(1)->hour();

    StartImpersonation::make()->execute($impersonator, $user, route('internal.controls.users.show', $user));
    $this->get(route('dashboard'))->assertOk();

    expect(UserSession::query()->sole()->only('id', 'user_id', 'impersonator_id', 'session_id'))->toBe([
        'id' => $session->id,
        'user_id' => $user->id,
        'impersonator_id' => $impersonator->id,
        'session_id' => session()->getId(),
    ]);

    $this->post(route('impersonation.stop'));

    $stopped = UserSession::query()->sole();
    expect($stopped->only('id', 'user_id', 'impersonator_id'))->toBe([
        'id' => $session->id,
        'user_id' => $impersonator->id,
        'impersonator_id' => null,
    ])
        ->and($stopped->signed_in_at->equalTo($session->signed_in_at))->toBeTrue();
});

test('a session whose row is gone goes to the login page without a sign-out entry', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    UserSession::query()->sole()->forceDelete();

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(SignInEntry::query()->count())->toBe(0)
        ->and(UserSession::query()->count())->toBe(0);
});

test('a Livewire update of a session whose row is gone goes to the login page', function (): void {
    $user = User::factory()->create();
    $page = $this->actingAs($user)->get(route('dashboard'))->assertOk();
    UserSession::query()->sole()->forceDelete();

    $response = callLivewireAction($page, 'pages.app.dashboard', 'refresh');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('a session past the maximum days is signed out at its next request with one expired entry', function (): void {
    config(['system.sessions.max_days' => 90]);
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $session = UserSession::query()->sole();
    $session->forceFill(['signed_in_at' => now()->subDays(90)->subMinute()])->save();

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(UserSession::query()->count())->toBe(0)
        ->and(SignInEntry::query()->sole()->only('event', 'user_id', 'ended_by_id'))->toBe([
            'event' => SignInEvent::SessionExpired,
            'user_id' => $user->id,
            'ended_by_id' => null,
        ]);
});

test('a session inside the maximum days, or with no maximum, stays signed in', function (mixed $maxDays, int $days): void {
    config(['system.sessions.max_days' => $maxDays]);
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    UserSession::query()->sole()->forceFill(['signed_in_at' => now()->subDays($days)])->save();

    $this->get(route('dashboard'))->assertOk();

    $this->assertAuthenticatedAs($user);
    expect(SignInEntry::query()->count())->toBe(0);
})->with([
    'inside the maximum' => [90, 89],
    'an empty maximum' => ['', 400],
    'an invalid maximum' => ['abc', 400],
]);
