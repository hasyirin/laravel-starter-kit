<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Enums\SignInEvent;
use App\Domain\User\Models\SignInEntry;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserSession;
use App\Livewire\DataTables\Internal\User\SessionDataTable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Testing\TestResponse;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);

    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.users.view', 'internal.controls.users.update', 'internal.controls.users.update-sessions');
    $this->current = UserSession::factory()->forUser($this->actor)->create();
    $this->actingAs($this->actor)->withSession([UserSession::SESSION_KEY => $this->current->id]);

    $this->subject = User::factory()->create();
});

function sessionTable(User $user): Testable
{
    return Livewire::test(SessionDataTable::class, ['user' => $user]);
}

function endAllSessionsOnUserPage(TestResponse $page): TestResponse
{
    preg_match_all('/wire:snapshot="([^"]+)"/', (string) $page->getContent(), $matches);

    $snapshot = collect($matches[1])
        ->map(fn (string $snapshot): string => html_entity_decode($snapshot))
        ->first(fn (string $snapshot): bool => data_get(json_decode($snapshot, true), 'memo.name') === 'data-tables.internal.user.session-data-table');

    $calls = [
        ['method' => 'mountAction', 'params' => ['endAll', [], ['table' => true]], 'metadata' => []],
        ['method' => 'callMountedAction', 'params' => [], 'metadata' => []],
    ];
    $payload = json_encode(['components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => $calls]]]);

    return test()->call('POST', resolve('livewire')->getUpdateUri(), server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => '*/*', 'HTTP_X_LIVEWIRE' => '1'], content: $payload);
}

test('the sessions tab lists only the live sessions that the user owns', function (): void {
    $own = UserSession::factory()->forUser($this->subject)->create();
    $asImpersonator = UserSession::factory()->impersonatedBy($this->subject)->create();
    $impersonated = UserSession::factory()->forUser($this->subject)->impersonatedBy(User::factory()->create())->create();
    $idle = UserSession::factory()->forUser($this->subject)->create(['last_activity_at' => now()->subMinutes(config()->integer('session.lifetime') + 2)]);

    sessionTable($this->subject)
        ->assertCanSeeTableRecords([$own, $asImpersonator])
        ->assertCanNotSeeTableRecords([$impersonated, $idle, $this->current]);
});

test('the sessions tab needs update-sessions', function (): void {
    revokePermissions($this->actor, 'internal.controls.users.update-sessions');

    sessionTable($this->subject)->assertForbidden();
});

test('the sessions tab cannot reach a user with bypass-authorization', function (): void {
    grantPermissions($this->subject, 'bypass-authorization');

    sessionTable($this->subject)->assertForbidden();
});

test('end and end all ask for the password first and keep the sessions', function (string $action): void {
    $session = UserSession::factory()->forUser($this->subject)->create();
    $testAction = $action === 'end' ? TestAction::make('end')->table($session) : TestAction::make('endAll')->table();

    sessionTable($this->subject)
        ->callAction($testAction)
        ->assertRedirect(route('password.confirm'));

    $this->assertModelExists($session);
})->with(['end', 'endAll']);

test('after the password step staff come back to the sessions tab of the same user page', function (): void {
    $session = UserSession::factory()->forUser($this->subject)->create();
    $url = route('internal.controls.users.show', $this->subject);
    $page = $this->get($url.'?tab=sessions')->assertOk();

    endAllSessionsOnUserPage($page)->assertOk();

    $this->assertModelExists($session);
    expect(session('url.intended'))->toBe($url.'?tab=sessions');
});

test('after the password step end ends the session with the staff member as the one who ended it', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $session = UserSession::factory()->forUser($this->subject)->create();

    sessionTable($this->subject)->callAction(TestAction::make('end')->table($session));

    $this->assertModelMissing($session);
    expect(SignInEntry::query()->where('event', SignInEvent::SessionEnded)->sole()->ended_by_id)->toBe($this->actor->id);
});

test('end all ends the sessions of the user and leaves an impersonation of the user', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $sessions = UserSession::factory()->forUser($this->subject)->count(2)->create();
    $impersonation = UserSession::factory()->forUser($this->subject)->impersonatedBy(User::factory()->create())->create();

    sessionTable($this->subject)->callAction(TestAction::make('endAll')->table());

    $sessions->each(fn (UserSession $session) => $this->assertModelMissing($session));
    $this->assertModelExists($impersonation);
    $this->assertModelExists($this->current);
});

test('end all on your own page keeps the current session', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $other = UserSession::factory()->forUser($this->actor)->create();

    sessionTable($this->actor)
        ->assertActionHidden(TestAction::make('end')->table($this->current))
        ->callAction(TestAction::make('endAll')->table());

    $this->assertModelMissing($other);
    $this->assertModelExists($this->current);
});

test('an action is refused after the permission is gone', function (string $action): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $session = UserSession::factory()->forUser($this->subject)->create();
    $testAction = $action === 'end' ? TestAction::make('end')->table($session) : TestAction::make('endAll')->table();
    $table = sessionTable($this->subject);
    revokePermissions($this->actor, 'internal.controls.users.update-sessions');

    $table->callAction($testAction)->assertForbidden();

    $this->assertModelExists($session);
})->with(['end', 'endAll']);

test('a session that the user does not own cannot be ended', function (): void {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $session = UserSession::factory()->create();

    sessionTable($this->subject)
        ->mountAction(TestAction::make('end')->table($session))
        ->assertActionNotMounted()
        ->call('callMountedAction');

    $this->assertModelExists($session);
});

test('end all says that all sessions of another user end, and on your own page that the other sessions end', function (User $user, string $key): void {
    $action = sessionTable($user)
        ->mountAction(TestAction::make('endAll')->table())
        ->instance()
        ->getMountedAction();

    expect($action->getLabel())->toBe(__("user/sessions.{$key}.button"))
        ->and($action->getModalHeading())->toBe(__("user/sessions.{$key}.heading"))
        ->and($action->getModalDescription())->toBe(__("user/sessions.{$key}.description"));
})->with([
    'another user' => fn (): array => [test()->subject, 'end_all_of_user'],
    'your own page' => fn (): array => [test()->actor, 'end_all'],
]);
