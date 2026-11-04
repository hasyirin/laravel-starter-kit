<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Actions\UpdateRole;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\Role\ActivityDataTable;
use App\Livewire\Pages\Admin\Controls\Role\ActivityModal;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create(['name' => 'Ada Admin']);
    $this->admin->givePermissionTo('admin.access', 'admin.controls.roles.view', 'admin.controls.roles.view-activities');
    $this->actingAs($this->admin);
    $this->role = Role::query()->create(['name' => 'editor']);
    $this->other = Role::query()->create(['name' => 'other']);
});

test('the list shows only the changes to the role, with who made them', function (): void {
    UpdateRole::make()->execute($this->admin, $this->role, 'author', ['admin.access']);
    $onRole = Activity::query()->whereMorphedTo('subject', $this->role)->latest('id')->firstOrFail();
    $onOther = Activity::query()->whereMorphedTo('subject', $this->other)->firstOrFail();

    Livewire::test(ActivityDataTable::class, ['roleId' => $this->role->id])
        ->assertCanSeeTableRecords([$onRole])
        ->assertCanNotSeeTableRecords([$onOther])
        ->assertSee('Ada Admin');
});

test('the audit needs view-activities', function (string $component): void {
    $this->admin->revokePermissionTo('admin.controls.roles.view-activities');

    Livewire::test($component, ['roleId' => $this->role->id])->assertForbidden();
})->with([ActivityDataTable::class, ActivityModal::class]);

test('a row click opens the modal with the permissions that were removed and added', function (): void {
    $this->role->givePermissionTo('admin.horizon.view');
    UpdateRole::make()->execute($this->admin, $this->role, null, ['internal.access']);
    $activity = Activity::query()->whereMorphedTo('subject', $this->role)->latest('id')->firstOrFail();

    Livewire::test(ActivityDataTable::class, ['roleId' => $this->role->id])
        ->callAction(TestAction::make('view')->table($activity))
        ->assertDispatchedTo(ActivityModal::class, 'show', id: $activity->id);

    Livewire::test(ActivityModal::class, ['roleId' => $this->role->id])
        ->call('show', $activity->id)
        ->assertSeeInOrder(['Permissions', 'admin.horizon.view', 'internal.access']);
});

test('the modal refuses an activity on another role', function (): void {
    $activity = Activity::query()->whereMorphedTo('subject', $this->other)->firstOrFail();

    Livewire::test(ActivityModal::class, ['roleId' => $this->role->id])
        ->call('show', $activity->id)
        ->assertNotFound();
});
