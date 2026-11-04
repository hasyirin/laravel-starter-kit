<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Actions\UpdateRole;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Admin\Role\AuditLogDataTable;
use App\Livewire\Pages\Admin\Controls\Role\AuditEntryModal;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->admin = User::factory()->create(['name' => 'Ada Admin']);
    $this->admin->givePermissionTo('admin.access', 'admin.controls.roles.view', 'admin.controls.roles.view-audit-log');
    $this->actingAs($this->admin);
    $this->role = Role::query()->create(['name' => 'editor']);
    $this->other = Role::query()->create(['name' => 'other']);
});

test('the list shows only the changes to the role, with who made them', function (): void {
    UpdateRole::make()->execute($this->admin, $this->role, 'author', ['admin.access']);
    $onRole = Activity::query()->whereMorphedTo('subject', $this->role)->latest('id')->firstOrFail();
    $onOther = Activity::query()->whereMorphedTo('subject', $this->other)->firstOrFail();

    Livewire::test(AuditLogDataTable::class, ['role' => $this->role])
        ->assertCanSeeTableRecords([$onRole])
        ->assertCanNotSeeTableRecords([$onOther])
        ->assertSee('Ada Admin');
});

test('the audit needs view-audit-log', function (string $component): void {
    $this->admin->revokePermissionTo('admin.controls.roles.view-audit-log');

    Livewire::test($component, ['role' => $this->role])->assertForbidden();
})->with([AuditLogDataTable::class, AuditEntryModal::class]);

test('a row click opens the modal with the permissions that were removed and added', function (): void {
    $this->role->givePermissionTo('admin.horizon.view');
    UpdateRole::make()->execute($this->admin, $this->role, null, ['internal.access']);
    $entry = Activity::query()->whereMorphedTo('subject', $this->role)->latest('id')->firstOrFail();

    Livewire::test(AuditLogDataTable::class, ['role' => $this->role])
        ->callAction(TestAction::make('view')->table($entry))
        ->assertDispatchedTo(AuditEntryModal::class, 'show', id: $entry->id);

    Livewire::test(AuditEntryModal::class, ['role' => $this->role])
        ->call('show', $entry->id)
        ->assertSeeInOrder(['Permissions', 'admin.horizon.view', 'internal.access']);
});

test('the modal refuses an audit entry on another role', function (): void {
    $entry = Activity::query()->whereMorphedTo('subject', $this->other)->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['role' => $this->role])
        ->call('show', $entry->id)
        ->assertNotFound();
});
