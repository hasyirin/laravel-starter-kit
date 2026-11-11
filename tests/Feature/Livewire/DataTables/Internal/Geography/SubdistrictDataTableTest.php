<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteSubdistrict;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\SubdistrictDataTable;
use App\Livewire\Pages\Internal\Controls\Geography\SubdistrictModal;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => '0102']);
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view-any', 'internal.controls.geography.create', 'internal.controls.geography.delete');
    $this->actingAs($this->actor);
    $johor = State::factory()->for(Country::factory()->create(['code' => 'MYS', 'name' => 'Malaysia']))->create(['code' => '01', 'name' => 'Johor']);
    $this->muar = District::factory()->for($johor)->create(['code' => '0101', 'name' => 'Muar']);
    $this->batuPahat = District::factory()->for($johor)->create(['code' => '0102', 'name' => 'Batu Pahat']);
    $this->lenga = Subdistrict::factory()->for($this->muar)->create(['code' => '01', 'name' => 'Lenga']);
    $this->minyakBeku = Subdistrict::factory()->for($this->batuPahat)->create(['code' => '01', 'name' => 'Minyak Beku']);
});

test('the index tab lists every subdistrict with its district and state, local subdistricts first', function (): void {
    Livewire::test(SubdistrictDataTable::class)
        ->assertCanSeeTableRecords([$this->minyakBeku, $this->lenga], inOrder: true)
        ->assertTableColumnStateSet('district.name', 'Muar', $this->lenga)
        ->assertTableColumnStateSet('district.state.name', 'Johor', $this->lenga)
        ->assertActionHidden(TestAction::make('create')->table());
});

test('the index tab keeps local subdistricts first when the user sorts', function (): void {
    Livewire::test(SubdistrictDataTable::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords([$this->minyakBeku, $this->lenga], inOrder: true);
});

test('the index tab filters by district', function (): void {
    Livewire::test(SubdistrictDataTable::class)
        ->filterTable('district', $this->muar->id)
        ->assertCanSeeTableRecords([$this->lenga])
        ->assertCanNotSeeTableRecords([$this->minyakBeku]);
});

test("the district tab lists only that district's subdistricts", function (): void {
    Livewire::test(SubdistrictDataTable::class, ['district' => $this->muar])
        ->assertCanSeeTableRecords([$this->lenga])
        ->assertCanNotSeeTableRecords([$this->minyakBeku])
        ->assertTableColumnHidden('district.name');
});

test('the create action of the district tab opens the subdistrict modal', function (): void {
    Livewire::test(SubdistrictDataTable::class, ['district' => $this->muar])
        ->callAction(TestAction::make('create')->table())
        ->assertDispatchedTo(SubdistrictModal::class, 'show');
});

test('the create action of the district tab is hidden without create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    Livewire::test(SubdistrictDataTable::class, ['district' => $this->muar])->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the subdistrict in the modal', function (): void {
    Livewire::test(SubdistrictDataTable::class)
        ->callAction(TestAction::make('view')->table($this->lenga))
        ->assertDispatchedTo(SubdistrictModal::class, 'show', id: $this->lenga->id);
});

test('the delete action is hidden on a subdistrict that an address uses', function (): void {
    Address::factory()->for(Organization::factory(), 'addressable')->create(['subdistrict_id' => $this->lenga->id]);

    Livewire::test(SubdistrictDataTable::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->lenga))
        ->callAction(TestAction::make('delete')->table($this->minyakBeku));

    expect($this->minyakBeku->fresh()->trashed())->toBeTrue();
});

test('the delete check runs no query per row', function (): void {
    $countQueries = function (): int {
        Livewire::withoutLazyLoading();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(SubdistrictDataTable::class);

        return count(DB::getQueryLog());
    };

    Subdistrict::factory()->for($this->muar)->create();
    // The first render also loads the permissions of the user.
    $countQueries();
    $queriesForThreeRows = $countQueries();

    Subdistrict::factory()->count(7)->create();

    expect($countQueries())->toBe($queriesForThreeRows);
});

test('the table hides delete exactly when the delete action refuses it', function (Closure $arrange, bool $blocked): void {
    $subdistrict = $arrange();
    $delete = TestAction::make('delete')->table($subdistrict);
    $table = Livewire::test(SubdistrictDataTable::class);

    if ($blocked) {
        $table->assertActionHidden($delete);
    } else {
        $table->assertActionVisible($delete);
    }

    $refused = false;

    try {
        DeleteSubdistrict::make()->execute($subdistrict);
    } catch (LogicException) {
        $refused = true;
    }

    expect($refused)->toBe($blocked)
        ->and($subdistrict->fresh()->trashed())->toBe(! $blocked);
})->with([
    'a live address' => [function (): Subdistrict {
        $subdistrict = Subdistrict::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['subdistrict_id' => $subdistrict->id]);

        return $subdistrict;
    }, true],
    'only a deleted address' => [function (): Subdistrict {
        $subdistrict = Subdistrict::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['subdistrict_id' => $subdistrict->id])->delete();

        return $subdistrict;
    }, false],
]);
