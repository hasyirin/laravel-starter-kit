<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\User\ActivityDataTable;
use App\Livewire\Pages\Admin\Controls\User\ActivityModal;
use App\Livewire\Pages\Admin\Controls\User\Audit;
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
        ->assertSee('name');
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
        ->filterTable('subject_type', 'profile')
        ->assertCanSeeTableRecords([$onProfile])
        ->assertCanNotSeeTableRecords([$onUser]);
});

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
        ->assertSeeInOrder(['name', 'Sam Subject', 'Sam Renamed']);
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

test('the modal refuses an activity that does not involve the user', function (): void {
    $activity = renameAs($this->admin, $this->other, 'Olive Renamed');

    Livewire::test(ActivityModal::class, ['userId' => $this->subject->id])
        ->call('show', $activity->id)
        ->assertNotFound();
});
