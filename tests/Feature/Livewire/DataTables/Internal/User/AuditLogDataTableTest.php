<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Enums\AuditDirection;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use App\Livewire\DataTables\Internal\User\AuditLogDataTable;
use App\Livewire\Pages\Internal\Controls\User\Audit;
use App\Livewire\Pages\Internal\Controls\User\AuditEntryModal;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);

    $this->actor = User::factory()->create(['name' => 'Ada Lovelace']);
    grantPermissions($this->actor, 'internal.access', 'internal.controls.users.view', 'internal.controls.users.view-audit-log');

    $this->subject = User::factory()->create(['name' => 'Sam Subject']);
    $this->other = User::factory()->create(['name' => 'Olive Other']);
});

function auditLogTable(User $user, AuditDirection $direction): Testable
{
    return Livewire::test(AuditLogDataTable::class, ['user' => $user, 'direction' => $direction]);
}

function renameAs(User $causer, User $user, string $name): Activity
{
    test()->actingAs($causer);
    $user->update(['name' => $name]);

    return Activity::query()->latest('id')->firstOrFail();
}

test('by this user lists what the user did', function (): void {
    $did = renameAs($this->subject, $this->other, 'Olive Renamed');
    $doneToThem = renameAs($this->actor, $this->subject, 'Sam Renamed');

    auditLogTable($this->subject, AuditDirection::By)
        ->assertCanSeeTableRecords([$did])
        ->assertCanNotSeeTableRecords([$doneToThem])
        ->assertSee('User Olive Renamed');
});

test('on this user lists changes to the user and their profiles', function (): void {
    $onUser = renameAs($this->actor, $this->subject, 'Sam Renamed');
    Profile::factory()->for($this->subject)->create();
    $onProfile = Activity::query()->latest('id')->firstOrFail();
    $onOther = renameAs($this->actor, $this->other, 'Olive Renamed');
    $byThem = renameAs($this->subject, $this->other, 'Olive Again');
    $this->actingAs($this->actor);

    auditLogTable($this->subject, AuditDirection::On)
        ->assertCanSeeTableRecords([$onUser, $onProfile])
        ->assertCanNotSeeTableRecords([$onOther, $byThem])
        ->assertSee('Ada Lovelace');
});

test('the subject type filter keeps only that type', function (): void {
    $onUser = renameAs($this->subject, $this->other, 'Olive Renamed');
    Profile::factory()->for($this->other)->create();
    $onProfile = Activity::query()->latest('id')->firstOrFail();
    $this->actingAs($this->actor);

    auditLogTable($this->subject, AuditDirection::By)
        ->filterTable('subject_type', ['profile'])
        ->assertCanSeeTableRecords([$onProfile])
        ->assertCanNotSeeTableRecords([$onUser]);
});

test('the event filter keeps only those events in both directions', function (AuditDirection $direction): void {
    $updated = renameAs($this->subject, $this->subject, 'Sam Renamed');
    Profile::factory()->for($this->subject)->create();
    $created = Activity::query()->latest('id')->firstOrFail();
    $created->update(['causer_type' => $this->subject->getMorphClass(), 'causer_id' => $this->subject->id]);
    $this->actingAs($this->actor);

    auditLogTable($this->subject, $direction)
        ->filterTable('event', ['created'])
        ->assertCanSeeTableRecords([$created])
        ->assertCanNotSeeTableRecords([$updated]);
})->with(AuditDirection::cases());

test('the time filter keeps the days that the viewer picks, in the time zone of the viewer', function (): void {
    context()->addHidden(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-GB']);
    $early = renameAs($this->actor, $this->subject, 'Sam Early');
    $early->forceFill(['created_at' => CarbonImmutable::parse('2026-01-01 15:00', 'UTC')])->save();
    $late = renameAs($this->actor, $this->subject, 'Sam Late');
    $late->forceFill(['created_at' => CarbonImmutable::parse('2026-01-01 17:00', 'UTC')])->save();

    auditLogTable($this->subject, AuditDirection::On)
        ->filterTable('created_at', ['from' => '2026-01-02', 'until' => '2026-01-02'])
        ->assertCanSeeTableRecords([$late])
        ->assertCanNotSeeTableRecords([$early]);
});

test('the search finds an entry by who did it, a changed value, or its event', function (string $search): void {
    $byActor = renameAs($this->actor, $this->subject, 'Sam Renamed');
    $byOther = renameAs($this->other, $this->subject, 'Sam Again');
    Profile::factory()->for($this->subject)->create();
    $created = Activity::query()->latest('id')->firstOrFail();
    $this->actingAs($this->actor);

    $expected = ['ada' => $byActor, 'again' => $byOther, 'created' => $created][$search];

    auditLogTable($this->subject, AuditDirection::On)
        ->searchTable(strtoupper($search))
        ->assertCanSeeTableRecords([$expected])
        ->assertCanNotSeeTableRecords(collect([$byActor, $byOther, $created])->reject(fn (Activity $entry): bool => $entry->is($expected))->all());
})->with(['who did it' => ['ada'], 'a changed value' => ['again'], 'its event' => ['created']]);

test('the audit tab needs view-audit-log', function (string $component, array $params): void {
    revokePermissions($this->actor, 'internal.controls.users.view-audit-log');
    $this->actingAs($this->actor);

    Livewire::test($component, ['user' => $this->subject, ...$params])->assertForbidden();
})->with([
    'the tab' => [Audit::class, []],
    'the table' => [AuditLogDataTable::class, ['direction' => AuditDirection::By]],
    'the modal' => [AuditEntryModal::class, []],
]);

test('the switch is kept in the URL', function (): void {
    $this->actingAs($this->actor);

    Livewire::withQueryParams(['direction' => 'on'])
        ->test(Audit::class, ['user' => $this->subject])
        ->assertSet('direction', AuditDirection::On)
        ->assertSeeLivewire(AuditLogDataTable::class);
});

test('an unknown direction in the URL falls back to by this user', function (string $direction): void {
    $this->actingAs($this->actor);

    Livewire::withQueryParams(['direction' => $direction])
        ->test(Audit::class, ['user' => $this->subject])
        ->assertSet('direction', AuditDirection::By);
})->with(['an unknown value' => ['sideways'], 'an empty value' => ['']]);

test('the switch shows by this user and on this user', function (): void {
    $this->actingAs($this->actor);

    Livewire::test(Audit::class, ['user' => $this->subject])
        ->assertSeeInOrder(['By this user', 'On this user']);
});

test('a row click opens the modal with the audit entry', function (): void {
    $entry = renameAs($this->actor, $this->subject, 'Sam Renamed');

    auditLogTable($this->subject, AuditDirection::On)
        ->callAction(TestAction::make('view')->table($entry))
        ->assertDispatchedTo(AuditEntryModal::class, 'show', id: $entry->id);
});

test('the modal shows each changed field with its old and new value', function (): void {
    $entry = renameAs($this->actor, $this->subject, 'Sam Renamed');

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->assertSet('showing', true)
        ->assertSeeInOrder(['Name', 'Sam Subject', 'Sam Renamed']);
});

test('a profile entry shows the roles of that profile', function (): void {
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    $this->actingAs($this->actor);
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('system.access')->create();
    $entry = Activity::query()->latest('id')->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->assertSee('editor')
        ->assertSee('system.access');
});

test('a profile entry still shows its roles on the next request', function (): void {
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    $this->actingAs($this->actor);
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('system.access')->create();
    $entry = Activity::query()->latest('id')->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->call('$refresh')
        ->assertSee('editor')
        ->assertSee('system.access');
});

test('a profile entry shows field labels and the names of related records', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme Holdings']);
    $this->actingAs($this->actor);
    Profile::factory()->for($this->subject)->for($organization)->create();
    $entry = Activity::query()->latest('id')->firstOrFail();

    auditLogTable($this->subject, AuditDirection::On)->assertSee('Organization');

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->assertSeeInOrder(['Organization', 'Acme Holdings'])
        ->assertSeeInOrder(['User', 'Sam Subject'])
        ->assertDontSee('organization_id');
});

test('the modal shows dates for the viewer and booleans as yes or no', function (): void {
    $this->actingAs($this->actor);
    $verifiedAt = CarbonImmutable::parse('2026-01-02 03:04:05');
    $entry = Activity::query()->create([
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => 'user',
        'subject_id' => $this->subject->id,
        'attribute_changes' => ['attributes' => ['email_verified_at' => $verifiedAt->toJSON(), 'is_owner' => true], 'old' => ['email_verified_at' => null, 'is_owner' => false]],
    ]);

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->assertSee(Preferences::current()->datetime($verifiedAt))
        ->assertSeeInOrder(['No', 'Yes']);
});

test('the modal shows a list as the items that were removed and added', function (): void {
    $this->actingAs($this->actor);
    $entry = Activity::query()->create([
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => 'user',
        'subject_id' => $this->subject->id,
        'attribute_changes' => ['attributes' => ['permissions' => ['system.access', 'internal.access']], 'old' => ['permissions' => ['system.access', 'system.horizon.view']]],
    ]);

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->assertSeeInOrder(['Permissions', 'system.horizon.view', 'internal.access'])
        ->assertDontSee('system.access');
});

test('the modal refuses an audit entry that does not involve the user', function (): void {
    $entry = renameAs($this->actor, $this->other, 'Olive Renamed');

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->assertNotFound();
});

test('the modal shows the impersonator of an entry', function (): void {
    $impersonator = User::factory()->create(['name' => 'Ina Impersonator']);
    context()->add('impersonator_id', $impersonator->id);
    $entry = renameAs($this->actor, $this->subject, 'Sam Renamed');
    $impersonator->delete();

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->assertSee(__('user/users.impersonation.by', ['name' => 'Ina Impersonator']));
});

test('the modal shows no impersonator for an entry without one', function (): void {
    $entry = renameAs($this->actor, $this->subject, 'Sam Renamed');

    Livewire::test(AuditEntryModal::class, ['user' => $this->subject])
        ->call('show', $entry->id)
        ->assertDontSee(__('user/users.impersonation.by', ['name' => '']));
});
