<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Organization\DepartmentDataTable;
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
    $this->department = Department::factory()->for($this->organization)->create();
});

test('the departments tab is the first tab of the organization', function (): void {
    Livewire::test(Tabs::class, ['organization' => $this->organization])
        ->assertSeeLivewire(DepartmentDataTable::class);
});

test('the departments tab lists the departments of the organization only', function (): void {
    $other = Department::factory()->create();

    Livewire::test(DepartmentDataTable::class, ['organization' => $this->organization])
        ->assertCanSeeTableRecords([$this->department])
        ->assertCanNotSeeTableRecords([$other]);
});

test('the list shows whether a department is the owner department', function (): void {
    Livewire::test(DepartmentDataTable::class, ['organization' => Organization::owner()])
        ->assertTableColumnStateSet('is_owner', true, Department::owner());
});

test('the list needs organization view', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view');

    Livewire::test(DepartmentDataTable::class, ['organization' => $this->organization])->assertForbidden();
});

test('the create action goes to the create page', function (): void {
    Livewire::test(DepartmentDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('internal.controls.organizations.departments.create', $this->organization));
});

test('the create action is hidden without organization update', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(DepartmentDataTable::class, ['organization' => $this->organization])
        ->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the department', function (): void {
    Livewire::test(DepartmentDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('view')->table($this->department))
        ->assertRedirect(route('internal.controls.organizations.departments.show', [$this->organization, $this->department]));
});

test('the delete action deletes a department without placed users', function (): void {
    Livewire::test(DepartmentDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('delete')->table($this->department));

    expect($this->department->fresh()->trashed())->toBeTrue();
});

test('the delete action is hidden on a department with placed users', function (): void {
    Profile::factory()->for(User::factory())->create(['organization_id' => $this->organization->id, 'department_id' => $this->department->id]);

    Livewire::test(DepartmentDataTable::class, ['organization' => $this->organization])
        ->assertActionHidden(TestAction::make('delete')->table($this->department));
});

test('the delete action is hidden on the owner department', function (): void {
    Livewire::test(DepartmentDataTable::class, ['organization' => Organization::owner()])
        ->assertActionHidden(TestAction::make('delete')->table(Department::owner()));
});
