<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Enums\Feature;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use App\Domain\User\Support\Impersonation;
use App\Livewire\DataTables\Settings\SessionDataTable;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->current = UserSession::factory()->forUser($this->user)->create();
    $this->actingAs($this->user)->withSession([UserSession::SESSION_KEY => $this->current->id]);
});

function confirmPasswordForSessions(): void
{
    test()->withSession(['auth.password_confirmed_at' => time()]);
}

function sessionEndedBy(): ?int
{
    return SignInEntry::query()->where('event', SignInEvent::SessionEnded)->sole()->ended_by_id;
}

test('the table lists only the live sessions that the user owns', function (): void {
    config(['system.sessions.max_days' => 90]);
    $other = UserSession::factory()->forUser($this->user)->create();
    $idle = UserSession::factory()->forUser($this->user)->create(['last_activity_at' => now()->subMinutes(config()->integer('session.lifetime') + 2)]);
    $pastMaximum = UserSession::factory()->forUser($this->user)->create(['signed_in_at' => now()->subDays(91)]);
    $someoneElse = UserSession::factory()->create();

    Livewire::test(SessionDataTable::class)
        ->assertCanSeeTableRecords([$this->current, $other])
        ->assertCanNotSeeTableRecords([$idle, $pastMaximum, $someoneElse]);
});

test('a row shows the browser and platform and the IP address, and never the session id', function (): void {
    $session = UserSession::factory()->forUser($this->user)->create([
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        'ip_address' => '192.0.2.44',
    ]);

    Livewire::test(SessionDataTable::class)
        ->assertTableColumnStateSet('browser', 'Chrome on Windows', $session)
        ->assertSee('192.0.2.44')
        ->assertDontSee($session->session_id);
});

test('the last activity shows in the time zone of the user', function (): void {
    config(['system.timezone' => 'UTC']);
    $this->travelTo(CarbonImmutable::parse('2026-11-27 01:31', 'UTC'));
    $this->user->forceFill(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-GB'])->save();
    UserSession::factory()->forUser($this->user)->create(['last_activity_at' => CarbonImmutable::parse('2026-11-27 01:30', 'UTC')]);
    $this->withSession(['auth.password_confirmed_at' => now()->unix()]);

    $this->get(route('settings', ['tab' => 'security']))
        ->assertOk()
        ->assertSee('27 Nov 2026, 09:30')
        ->assertDontSee('27 Nov 2026, 01:30');
});

test('the newest activity comes first', function (): void {
    $older = UserSession::factory()->forUser($this->user)->create(['last_activity_at' => now()->subMinutes(2)]);
    $newer = UserSession::factory()->forUser($this->user)->create(['last_activity_at' => now()->subMinute()]);
    $this->current->update(['last_activity_at' => now()->subMinutes(3)]);

    Livewire::test(SessionDataTable::class)->assertCanSeeTableRecords([$newer, $older, $this->current], inOrder: true);
});

test('the current row shows This session and has no end action', function (): void {
    $other = UserSession::factory()->forUser($this->user)->create();

    Livewire::test(SessionDataTable::class)
        ->assertSee(__('user/sessions.current'))
        ->assertActionHidden(TestAction::make('end')->table($this->current))
        ->assertActionVisible(TestAction::make('end')->table($other));
});

test('the impersonator sees the impersonation session marked with the name of the impersonated user', function (): void {
    $impersonated = User::factory()->create(['name' => 'Aminah Binti Ali']);
    $impersonation = UserSession::factory()->forUser($impersonated)->impersonatedBy($this->user)->create();

    Livewire::test(SessionDataTable::class)
        ->assertCanSeeTableRecords([$impersonation])
        ->assertSee(__('user/sessions.impersonating', ['name' => 'Aminah Binti Ali']));
});

test('the impersonated user does not see the impersonation session', function (): void {
    $impersonator = User::factory()->create();
    $impersonation = UserSession::factory()->forUser($this->user)->impersonatedBy($impersonator)->create();

    Livewire::test(SessionDataTable::class)->assertCanNotSeeTableRecords([$impersonation]);
});

test('end asks for the password first and keeps the session', function (): void {
    $other = UserSession::factory()->forUser($this->user)->create();

    Livewire::test(SessionDataTable::class)
        ->callAction(TestAction::make('end')->table($other))
        ->assertRedirect(route('password.confirm'));

    $this->assertModelExists($other);
    expect(session('url.intended'))->toBe(route('settings', ['tab' => 'security']));
});

test('end all asks for the password first and keeps the sessions', function (): void {
    $other = UserSession::factory()->forUser($this->user)->create();

    Livewire::test(SessionDataTable::class)
        ->callAction(TestAction::make('endAll')->table())
        ->assertRedirect(route('password.confirm'));

    $this->assertModelExists($other);
});

test('after the password step end ends the session with the user as the one who ended it', function (): void {
    confirmPasswordForSessions();
    $other = UserSession::factory()->forUser($this->user)->create();

    Livewire::test(SessionDataTable::class)
        ->callAction(TestAction::make('end')->table($other))
        ->assertNoRedirect();

    $this->assertModelMissing($other);
    expect(sessionEndedBy())->toBe($this->user->id);
});

test('after the password step end all ends every other session of the user and keeps the current one', function (): void {
    confirmPasswordForSessions();
    $others = UserSession::factory()->forUser($this->user)->count(2)->create();
    $someoneElse = UserSession::factory()->create();

    Livewire::test(SessionDataTable::class)
        ->callAction(TestAction::make('endAll')->table())
        ->assertNoRedirect();

    $others->each(fn (UserSession $session) => $this->assertModelMissing($session));
    $this->assertModelExists($this->current);
    $this->assertModelExists($someoneElse);
    expect(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->pluck('ended_by_id')->all())->toBe([$this->user->id, $this->user->id]);
});

test('during an impersonation the impersonator is the one who ended the session, and the impersonation stays', function (): void {
    $impersonator = User::factory()->create();
    $this->current->update(['impersonator_id' => $impersonator->id]);
    Impersonation::begin($impersonator, '/');
    confirmPasswordForSessions();
    $other = UserSession::factory()->forUser($this->user)->create();

    Livewire::test(SessionDataTable::class)->callAction(TestAction::make('endAll')->table());

    $this->assertModelMissing($other);
    $this->assertModelExists($this->current);
    expect(sessionEndedBy())->toBe($impersonator->id);
});

test('a forged end of the current session does nothing', function (): void {
    confirmPasswordForSessions();

    Livewire::test(SessionDataTable::class)
        ->mountAction(TestAction::make('end')->table($this->current))
        ->assertActionNotMounted()
        ->call('callMountedAction');

    $this->assertModelExists($this->current);
});

test('a session that the user does not own cannot be ended', function (UserSession $session): void {
    confirmPasswordForSessions();

    Livewire::test(SessionDataTable::class)
        ->mountAction(TestAction::make('end')->table($session))
        ->assertActionNotMounted()
        ->call('callMountedAction');

    $this->assertModelExists($session);
})->with([
    'another user' => fn (): UserSession => UserSession::factory()->create(),
    'an impersonation of the user' => fn (): UserSession => UserSession::factory()->forUser(test()->user)->impersonatedBy(User::factory()->create())->create(),
]);

test('a user with an unverified email goes to the verification page instead of the table', function (): void {
    $this->skipUnlessFeature(Feature::EmailVerification);

    $this->actingAs(User::factory()->unverified()->create());

    Livewire::test(SessionDataTable::class)
        ->assertStatus(302)
        ->assertHeader('Location', route('verification.notice'));
});
