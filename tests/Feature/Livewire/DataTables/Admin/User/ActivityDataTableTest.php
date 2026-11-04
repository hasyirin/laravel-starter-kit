<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use App\Livewire\DataTables\Admin\User\ActivityDataTable;
use App\Livewire\Pages\Admin\Controls\User\ActivityModal;
use App\Livewire\Pages\Admin\Controls\User\Audit;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create(['name' => 'Ada Admin']);
    $this->admin->givePermissionTo('admin.access', 'admin.controls.users.view', 'admin.controls.users.view-activities');

    $this->subject = User::factory()->create(['name' => 'Sam Subject']);
    $this->other = User::factory()->create(['name' => 'Olive Other']);
});

function activityTable(User $user, string $direction): Testable
{
    return Livewire::test(ActivityDataTable::class, ['userId' => $user->id, 'direction' => $direction]);
}

function renameAs(User $causer, User $user, string $name): Activity
{
    test()->actingAs($causer);
    $user->update(['name' => $name]);

    return Activity::query()->latest('id')->firstOrFail();
}

test('by this user lists what the user did', function (): void {
    $did = renameAs($this->subject, $this->other, 'Olive Renamed');
    $doneToThem = renameAs($this->admin, $this->subject, 'Sam Renamed');

    activityTable($this->subject, 'by')
        ->assertCanSeeTableRecords([$did])
        ->assertCanNotSeeTableRecords([$doneToThem])
        ->assertSee('User Olive Renamed');
});

test('on this user lists changes to the user and their profiles', function (): void {
    $onUser = renameAs($this->admin, $this->subject, 'Sam Renamed');
    Profile::factory()->for($this->subject)->create();
    $onProfile = Activity::query()->latest('id')->firstOrFail();
    $onOther = renameAs($this->admin, $this->other, 'Olive Renamed');
    $byThem = renameAs($this->subject, $this->other, 'Olive Again');
    $this->actingAs($this->admin);

    activityTable($this->subject, 'on')
        ->assertCanSeeTableRecords([$onUser, $onProfile])
        ->assertCanNotSeeTableRecords([$onOther, $byThem])
        ->assertSee('Ada Admin');
});

test('the subject type filter keeps only that type', function (): void {
    $onUser = renameAs($this->subject, $this->other, 'Olive Renamed');
    Profile::factory()->for($this->other)->create();
    $onProfile = Activity::query()->latest('id')->firstOrFail();
    $this->actingAs($this->admin);

    activityTable($this->subject, 'by')
        ->filterTable('subject_type', ['profile'])
        ->assertCanSeeTableRecords([$onProfile])
        ->assertCanNotSeeTableRecords([$onUser]);
});

test('the event filter keeps only those events in both directions', function (string $direction): void {
    $updated = renameAs($this->subject, $this->subject, 'Sam Renamed');
    Profile::factory()->for($this->subject)->create();
    $created = Activity::query()->latest('id')->firstOrFail();
    $created->update(['causer_type' => $this->subject->getMorphClass(), 'causer_id' => $this->subject->id]);
    $this->actingAs($this->admin);

    activityTable($this->subject, $direction)
        ->filterTable('event', ['created'])
        ->assertCanSeeTableRecords([$created])
        ->assertCanNotSeeTableRecords([$updated]);
})->with(['by', 'on']);

test('the time filter keeps the days that the viewer picks, in the time zone of the viewer', function (): void {
    context()->addHidden(['timezone' => 'Asia/Kuala_Lumpur', 'locale' => 'en-GB']);
    $early = renameAs($this->admin, $this->subject, 'Sam Early');
    $early->forceFill(['created_at' => CarbonImmutable::parse('2026-01-01 15:00', 'UTC')])->save();
    $late = renameAs($this->admin, $this->subject, 'Sam Late');
    $late->forceFill(['created_at' => CarbonImmutable::parse('2026-01-01 17:00', 'UTC')])->save();

    activityTable($this->subject, 'on')
        ->filterTable('created_at', ['from' => '2026-01-02', 'until' => '2026-01-02'])
        ->assertCanSeeTableRecords([$late])
        ->assertCanNotSeeTableRecords([$early]);
});

test('the search finds an entry by who did it, a changed value, or its event', function (string $search): void {
    $byAdmin = renameAs($this->admin, $this->subject, 'Sam Renamed');
    $byOther = renameAs($this->other, $this->subject, 'Sam Again');
    Profile::factory()->for($this->subject)->create();
    $created = Activity::query()->latest('id')->firstOrFail();
    $this->actingAs($this->admin);

    $expected = ['ada' => $byAdmin, 'again' => $byOther, 'created' => $created][$search];

    activityTable($this->subject, 'on')
        ->searchTable(strtoupper($search))
        ->assertCanSeeTableRecords([$expected])
        ->assertCanNotSeeTableRecords(collect([$byAdmin, $byOther, $created])->reject(fn (Activity $activity): bool => $activity->is($expected))->all());
})->with(['who did it' => ['ada'], 'a changed value' => ['again'], 'its event' => ['created']]);

test('the audit tab needs view-activities', function (string $component, array $params): void {
    $this->admin->revokePermissionTo('admin.controls.users.view-activities');
    $this->actingAs($this->admin);

    Livewire::test($component, ['userId' => $this->subject->id, ...$params])->assertForbidden();
})->with([
    'the tab' => [Audit::class, []],
    'the table' => [ActivityDataTable::class, ['direction' => 'by']],
    'the modal' => [ActivityModal::class, []],
]);

test('the switch is kept in the URL', function (): void {
    $this->actingAs($this->admin);

    Livewire::withQueryParams(['direction' => 'on'])
        ->test(Audit::class, ['userId' => $this->subject->id])
        ->assertSet('direction', 'on')
        ->assertSeeLivewire(ActivityDataTable::class);
});

test('a row click opens the modal with the activity', function (): void {
    $activity = renameAs($this->admin, $this->subject, 'Sam Renamed');

    activityTable($this->subject, 'on')
        ->callAction(TestAction::make('view')->table($activity))
        ->assertDispatchedTo(ActivityModal::class, 'show', id: $activity->id);
});

test('the modal shows each changed field with its old and new value', function (): void {
    $activity = renameAs($this->admin, $this->subject, 'Sam Renamed');

    Livewire::test(ActivityModal::class, ['userId' => $this->subject->id])
        ->call('show', $activity->id)
        ->assertSet('open', true)
        ->assertSeeInOrder(['Name', 'Sam Subject', 'Sam Renamed']);
});

test('a profile entry shows the roles of that profile', function (): void {
    Role::query()->create(['name' => 'editor', 'guard_name' => 'web']);
    $this->actingAs($this->admin);
    Profile::factory()->for($this->subject)->withRoles('editor')->withPermissions('admin.access')->create();
    $activity = Activity::query()->latest('id')->firstOrFail();

    Livewire::test(ActivityModal::class, ['userId' => $this->subject->id])
        ->call('show', $activity->id)
        ->assertSee('editor')
        ->assertSee('admin.access');
});

test('a profile entry shows field labels and the names of related records', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme Holdings']);
    $this->actingAs($this->admin);
    Profile::factory()->for($this->subject)->for($organization)->create();
    $activity = Activity::query()->latest('id')->firstOrFail();

    activityTable($this->subject, 'on')->assertSee('Organization');

    Livewire::test(ActivityModal::class, ['userId' => $this->subject->id])
        ->call('show', $activity->id)
        ->assertSeeInOrder(['Organization', 'Acme Holdings'])
        ->assertSeeInOrder(['User', 'Sam Subject'])
        ->assertDontSee('organization_id');
});

test('the modal shows dates for the viewer and booleans as yes or no', function (): void {
    $this->actingAs($this->admin);
    $verifiedAt = CarbonImmutable::parse('2026-01-02 03:04:05');
    $activity = Activity::query()->create([
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => 'user',
        'subject_id' => $this->subject->id,
        'attribute_changes' => ['attributes' => ['email_verified_at' => $verifiedAt->toJSON(), 'is_owner' => true], 'old' => ['email_verified_at' => null, 'is_owner' => false]],
    ]);

    Livewire::test(ActivityModal::class, ['userId' => $this->subject->id])
        ->call('show', $activity->id)
        ->assertSee(Preferences::current()->datetime($verifiedAt))
        ->assertSeeInOrder(['No', 'Yes']);
});

test('the modal shows a list as the items that were removed and added', function (): void {
    $this->actingAs($this->admin);
    $activity = Activity::query()->create([
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => 'user',
        'subject_id' => $this->subject->id,
        'attribute_changes' => ['attributes' => ['permissions' => ['admin.access', 'internal.access']], 'old' => ['permissions' => ['admin.access', 'admin.horizon.view']]],
    ]);

    Livewire::test(ActivityModal::class, ['userId' => $this->subject->id])
        ->call('show', $activity->id)
        ->assertSeeInOrder(['Permissions', 'admin.horizon.view', 'internal.access'])
        ->assertDontSee('admin.access');
});

test('the modal refuses an activity that does not involve the user', function (): void {
    $activity = renameAs($this->admin, $this->other, 'Olive Renamed');

    Livewire::test(ActivityModal::class, ['userId' => $this->subject->id])
        ->call('show', $activity->id)
        ->assertNotFound();
});
