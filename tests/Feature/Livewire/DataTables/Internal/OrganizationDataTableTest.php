<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\OrganizationDataTable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view-any', 'internal.controls.organizations.create', 'internal.controls.organizations.delete');
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create(['code' => 'ACME', 'name' => 'Acme']);
});

test('the list shows each organization with its counts and whether it is the owner', function (): void {
    Branch::factory()->for($this->organization)->count(2)->create();
    Department::factory()->for($this->organization)->count(3)->create();
    $owner = Organization::owner();

    Livewire::test(OrganizationDataTable::class)
        ->assertCanSeeTableRecords([$this->organization, $owner])
        ->assertTableColumnStateSet('branches_count', 2, $this->organization)
        ->assertTableColumnStateSet('departments_count', 3, $this->organization)
        ->assertTableColumnStateSet('is_owner', true, $owner)
        ->assertTableColumnStateSet('is_owner', false, $this->organization);
});

test('the list searches by code and name', function (): void {
    $other = Organization::factory()->create(['code' => 'XYZ', 'name' => 'Other']);

    Livewire::test(OrganizationDataTable::class)
        ->searchTable('ACME')
        ->assertCanSeeTableRecords([$this->organization])
        ->assertCanNotSeeTableRecords([$other])
        ->searchTable('Other')
        ->assertCanSeeTableRecords([$other])
        ->assertCanNotSeeTableRecords([$this->organization]);
});

test('the table refuses a user without view-any', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view-any');

    Livewire::test(OrganizationDataTable::class)->assertForbidden();
});

test('the create action goes to the create page', function (): void {
    Livewire::test(OrganizationDataTable::class)
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('internal.controls.organizations.create'));
});

test('the row click opens the organization', function (): void {
    grantPermissions($this->actor, 'internal.controls.organizations.view');

    Livewire::test(OrganizationDataTable::class)
        ->callAction(TestAction::make('view')->table($this->organization))
        ->assertRedirect(route('internal.controls.organizations.show', $this->organization));
});

test('the delete action deletes an organization without placed users', function (): void {
    Livewire::test(OrganizationDataTable::class)
        ->callAction(TestAction::make('delete')->table($this->organization));

    expect($this->organization->fresh()->trashed())->toBeTrue();
});

test('the delete action is hidden on the owner and on an organization with placed users', function (): void {
    Profile::factory()->for(User::factory())->create(['organization_id' => $this->organization->id]);

    Livewire::test(OrganizationDataTable::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->organization))
        ->assertActionHidden(TestAction::make('delete')->table(Organization::owner()));
});

test('the delete action is hidden without delete', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.delete');

    Livewire::test(OrganizationDataTable::class)->assertActionHidden(TestAction::make('delete')->table($this->organization));
});
