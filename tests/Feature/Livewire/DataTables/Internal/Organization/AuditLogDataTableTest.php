<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Actions\DeleteDepartment;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Organization\AuditLogDataTable;
use App\Livewire\Pages\Internal\Controls\Organization\AuditEntryModal;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create(['name' => 'Ada Lovelace']);
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', 'internal.controls.organizations.view-audit-log');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create(['name' => 'Finance']);
    $this->other = Organization::factory()->create();
});

test('the list shows the organization log with who made each change', function (): void {
    $this->organization->update(['name' => 'Renamed']);
    $onDepartment = Activity::query()->whereMorphedTo('subject', $this->department)->firstOrFail();
    $onOther = Activity::query()->whereMorphedTo('subject', $this->other)->firstOrFail();

    Livewire::test(AuditLogDataTable::class, ['record' => $this->organization])
        ->assertCanSeeTableRecords([$onDepartment])
        ->assertCanNotSeeTableRecords([$onOther])
        ->assertSee('Ada Lovelace');
});

test('the list names a deleted department', function (): void {
    DeleteDepartment::make()->execute($this->department);

    Livewire::test(AuditLogDataTable::class, ['record' => $this->organization])
        ->assertSee('Finance');
});

test('the audit needs view-audit-log', function (string $component): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view-audit-log');

    Livewire::test($component, ['record' => $this->organization])->assertForbidden();
})->with([AuditLogDataTable::class, AuditEntryModal::class]);

test('a row click opens the modal with the entry', function (): void {
    $entry = Activity::query()->whereMorphedTo('subject', $this->department)->firstOrFail();

    Livewire::test(AuditLogDataTable::class, ['record' => $this->organization])
        ->callAction(TestAction::make('view')->table($entry))
        ->assertDispatchedTo(AuditEntryModal::class, 'show', id: $entry->id);
});

test('the modal opens an entry of a department of the organization', function (): void {
    $entry = Activity::query()->whereMorphedTo('subject', $this->department)->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['record' => $this->organization])
        ->call('show', $entry->id)
        ->assertSet('showing', true);
});

test('the modal refuses an entry of another organization', function (): void {
    $entry = Activity::query()->whereMorphedTo('subject', $this->other)->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['record' => $this->organization])
        ->call('show', $entry->id)
        ->assertNotFound();
});
