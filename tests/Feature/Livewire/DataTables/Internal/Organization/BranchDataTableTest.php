<?php

declare(strict_types=1);

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Organization\BranchDataTable;
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
    $this->branch = Branch::factory()->for($this->organization)->create();
});

test('the organization has a branches tab', function (): void {
    Livewire::withQueryParams(['tab' => 'branches'])
        ->test(Tabs::class, ['organization' => $this->organization])
        ->assertSeeLivewire(BranchDataTable::class);
});

test('the branches tab lists the branches of the organization only', function (): void {
    $other = Branch::factory()->create();

    Livewire::test(BranchDataTable::class, ['organization' => $this->organization])
        ->assertCanSeeTableRecords([$this->branch])
        ->assertCanNotSeeTableRecords([$other]);
});

test('the list shows whether a branch is the owner branch', function (): void {
    Livewire::test(BranchDataTable::class, ['organization' => Organization::owner()])
        ->assertTableColumnStateSet('is_owner', true, Branch::owner());
});

test('the list needs organization view', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.view');

    Livewire::test(BranchDataTable::class, ['organization' => $this->organization])->assertForbidden();
});

test('the create action goes to the create page', function (): void {
    Livewire::test(BranchDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('internal.controls.organizations.branches.create', $this->organization));
});

test('the create action is hidden without organization update', function (): void {
    revokePermissions($this->actor, 'internal.controls.organizations.update');

    Livewire::test(BranchDataTable::class, ['organization' => $this->organization])
        ->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the branch', function (): void {
    Livewire::test(BranchDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('view')->table($this->branch))
        ->assertRedirect(route('internal.controls.organizations.branches.show', [$this->organization, $this->branch]));
});

test('the delete action deletes a branch without placed users', function (): void {
    Livewire::test(BranchDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('delete')->table($this->branch));

    expect($this->branch->fresh()->trashed())->toBeTrue();
});

test('the delete action is hidden on a branch with placed users', function (): void {
    Profile::factory()->for(User::factory())->create(['organization_id' => $this->organization->id, 'branch_id' => $this->branch->id]);

    Livewire::test(BranchDataTable::class, ['organization' => $this->organization])
        ->assertActionHidden(TestAction::make('delete')->table($this->branch));
});

test('the delete action is hidden on the owner branch', function (): void {
    Livewire::test(BranchDataTable::class, ['organization' => Organization::owner()])
        ->assertActionHidden(TestAction::make('delete')->table(Branch::owner()));
});
