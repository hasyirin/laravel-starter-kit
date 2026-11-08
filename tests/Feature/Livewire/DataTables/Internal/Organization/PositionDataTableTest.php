<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Organization\PositionDataTable;
use App\Livewire\Pages\Internal\Controls\Organization\Department\Tabs as DepartmentTabs;
use App\Livewire\Pages\Internal\Controls\Organization\PositionModal;
use App\Livewire\Pages\Internal\Controls\Organization\Positions;
use App\Livewire\Pages\Internal\Controls\Organization\Tabs;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', 'internal.controls.organizations.update');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create();
    $this->department = Department::factory()->for($this->organization)->create(['name' => 'Finance']);
    $this->inDepartment = Position::factory()->for($this->organization)->forDepartment($this->department)->create(['name' => 'Clerk']);
    $this->loose = Position::factory()->for($this->organization)->create(['name' => 'Chief Executive Officer']);
    $this->foreign = Position::factory()->create();
});

test('the organization has a positions tab', function (): void {
    Livewire::withQueryParams(['tab' => 'positions'])
        ->test(Tabs::class, ['organization' => $this->organization])
        ->assertSeeLivewire(Positions::class);
});

test('the positions tab is the first tab of the department', function (): void {
    Livewire::test(DepartmentTabs::class, ['organization' => $this->organization, 'department' => $this->department])
        ->assertSeeLivewire(Positions::class);
});

test('the organization tab lists all positions with their departments', function (): void {
    Livewire::test(PositionDataTable::class, ['organization' => $this->organization])
        ->assertCanSeeTableRecords([$this->inDepartment, $this->loose])
        ->assertCanNotSeeTableRecords([$this->foreign])
        ->assertTableColumnStateSet('department.name', 'Finance', $this->inDepartment)
        ->assertSee(__('organization/positions.no_department'));
});

test('the organization tab filters positions by department', function (): void {
    Livewire::test(PositionDataTable::class, ['organization' => $this->organization])
        ->filterTable('department', $this->department->id)
        ->assertCanSeeTableRecords([$this->inDepartment])
        ->assertCanNotSeeTableRecords([$this->loose]);
});

test("the department tab lists only that department's positions", function (): void {
    Livewire::test(PositionDataTable::class, ['organization' => $this->organization, 'department' => $this->department])
        ->assertCanSeeTableRecords([$this->inDepartment])
        ->assertCanNotSeeTableRecords([$this->loose, $this->foreign]);
});

test('the list needs organization view', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view');

    Livewire::test(PositionDataTable::class, ['organization' => $this->organization])->assertForbidden();
});

test('the create action opens the position modal', function (): void {
    Livewire::test(PositionDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('create')->table())
        ->assertDispatchedTo(PositionModal::class, 'show');
});

test('the create action is hidden without organization update', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(PositionDataTable::class, ['organization' => $this->organization])
        ->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the position in the modal', function (): void {
    Livewire::test(PositionDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('view')->table($this->loose))
        ->assertDispatchedTo(PositionModal::class, 'show', id: $this->loose->id);
});

test('deleting a position needs no placed users', function (): void {
    Profile::factory()->for(User::factory())->create(['organization_id' => $this->organization->id, 'department_id' => $this->department->id, 'position_id' => $this->inDepartment->id]);

    Livewire::test(PositionDataTable::class, ['organization' => $this->organization])
        ->assertActionHidden(TestAction::make('delete')->table($this->inDepartment))
        ->callAction(TestAction::make('delete')->table($this->loose));

    expect($this->loose->fresh()->trashed())->toBeTrue();
});
