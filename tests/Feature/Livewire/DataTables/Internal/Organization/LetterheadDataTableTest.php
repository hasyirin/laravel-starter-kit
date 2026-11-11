<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Organization\LetterheadDataTable;
use App\Livewire\Pages\Internal\Controls\Organization\LetterheadModal;
use App\Livewire\Pages\Internal\Controls\Organization\Letterheads;
use App\Livewire\Pages\Internal\Controls\Organization\Tabs;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.organizations.view', ...collect(['view-any', 'view', 'create', 'update', 'delete'])
        ->map(fn (string $permission): string => "internal.controls.letterheads.$permission")
        ->all());
    $this->actingAs($this->actor);
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->for($this->organization)->create(['name' => 'Kuching']);
    $this->general = Letterhead::factory()->for($this->organization)->create(['name' => 'General']);
    $this->forBranch = Letterhead::factory()->for($this->organization)->create(['name' => 'Kuching office', 'branch_id' => $this->branch->id]);
    $this->foreign = Letterhead::factory()->create();
});

test('the organization has a letterheads tab', function (): void {
    Livewire::withQueryParams(['tab' => 'letterheads'])
        ->test(Tabs::class, ['organization' => $this->organization])
        ->assertSeeLivewire(Letterheads::class);
});

test('the letterheads tab is hidden without letterhead view-any', function (): void {
    revokePermissions($this->actor, 'internal.controls.letterheads.view-any');

    Livewire::withQueryParams(['tab' => 'letterheads'])
        ->test(Tabs::class, ['organization' => $this->organization])
        ->assertDontSeeLivewire(Letterheads::class);
});

test('the table lists the letterheads of this organization only', function (): void {
    Livewire::test(LetterheadDataTable::class, ['organization' => $this->organization])
        ->assertCanSeeTableRecords([$this->general, $this->forBranch])
        ->assertCanNotSeeTableRecords([$this->foreign])
        ->assertTableColumnStateSet('branch.name', 'Kuching', $this->forBranch);
});

test('the table needs letterhead view-any', function (): void {
    revokePermissions($this->actor, 'internal.controls.letterheads.view-any');

    Livewire::test(LetterheadDataTable::class, ['organization' => $this->organization])->assertForbidden();
});

test('the create action opens the letterhead modal', function (): void {
    Livewire::test(LetterheadDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('create')->table())
        ->assertDispatchedTo(LetterheadModal::class, 'show');
});

test('the create action is hidden without letterhead create', function (): void {
    revokePermissions($this->actor, 'internal.controls.letterheads.create');

    Livewire::test(LetterheadDataTable::class, ['organization' => $this->organization])
        ->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the letterhead in the modal', function (): void {
    Livewire::test(LetterheadDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('view')->table($this->general))
        ->assertDispatchedTo(LetterheadModal::class, 'show', id: $this->general->id);
});

test('deleting a letterhead removes it', function (): void {
    Livewire::test(LetterheadDataTable::class, ['organization' => $this->organization])
        ->callAction(TestAction::make('delete')->table($this->general));

    expect($this->general->fresh()->trashed())->toBeTrue();
});

test('the delete action is hidden without letterhead delete', function (): void {
    revokePermissions($this->actor, 'internal.controls.letterheads.delete');

    Livewire::test(LetterheadDataTable::class, ['organization' => $this->organization])
        ->assertActionHidden(TestAction::make('delete')->table($this->general));
});
