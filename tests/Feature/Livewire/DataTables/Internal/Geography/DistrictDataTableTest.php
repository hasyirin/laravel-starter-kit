<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteDistrict;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\DistrictDataTable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    config()->set('address.locality', ['country' => 'MYS', 'state' => '01', 'district' => null]);
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view-any', 'internal.controls.geography.create', 'internal.controls.geography.delete');
    $this->actingAs($this->actor);
    $malaysia = Country::factory()->create(['code' => 'MYS', 'name' => 'Malaysia']);
    $this->johor = State::factory()->for($malaysia)->create(['code' => '01', 'name' => 'Johor']);
    $this->kedah = State::factory()->for($malaysia)->create(['code' => '02', 'name' => 'Kedah']);
    $this->muar = District::factory()->for($this->johor)->create(['code' => '0101', 'name' => 'Muar']);
    $this->baling = District::factory()->for($this->kedah)->create(['code' => '0201', 'name' => 'Baling']);
});

test('the index tab lists every district with its state and country, local districts first', function (): void {
    Livewire::test(DistrictDataTable::class)
        ->assertCanSeeTableRecords([$this->muar, $this->baling], inOrder: true)
        ->assertTableColumnStateSet('state.name', 'Kedah', $this->baling)
        ->assertTableColumnStateSet('state.country.name', 'Malaysia', $this->baling)
        ->assertActionHidden(TestAction::make('create')->table());
});

test('the index tab keeps local districts first when the user sorts', function (): void {
    Livewire::test(DistrictDataTable::class)
        ->sortTable('code', 'desc')
        ->assertCanSeeTableRecords([$this->muar, $this->baling], inOrder: true);
});

test('the index tab searches and filters by state', function (): void {
    Livewire::test(DistrictDataTable::class)
        ->searchTable('Kedah')
        ->assertCanSeeTableRecords([$this->baling])
        ->assertCanNotSeeTableRecords([$this->muar])
        ->searchTable()
        ->filterTable('state', $this->johor->id)
        ->assertCanSeeTableRecords([$this->muar])
        ->assertCanNotSeeTableRecords([$this->baling]);
});

test("the state tab lists only that state's districts", function (): void {
    Livewire::test(DistrictDataTable::class, ['state' => $this->kedah])
        ->assertCanSeeTableRecords([$this->baling])
        ->assertCanNotSeeTableRecords([$this->muar])
        ->assertTableColumnHidden('state.name')
        ->assertTableColumnHidden('state.country.name');
});

test('the create action of the state tab goes to the create page', function (): void {
    Livewire::test(DistrictDataTable::class, ['state' => $this->kedah])
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('internal.controls.geography.countries.states.districts.create', [$this->kedah->country, $this->kedah]));
});

test('the create action of the state tab is hidden without create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    Livewire::test(DistrictDataTable::class, ['state' => $this->kedah])->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the district under its state', function (): void {
    Livewire::test(DistrictDataTable::class)
        ->callAction(TestAction::make('view')->table($this->baling))
        ->assertRedirect(route('internal.controls.geography.countries.states.districts.show', [$this->kedah->country, $this->kedah, $this->baling]));
});

test('the delete action is hidden on a district with subdistricts', function (): void {
    Subdistrict::factory()->for($this->muar)->create();

    Livewire::test(DistrictDataTable::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->muar))
        ->callAction(TestAction::make('delete')->table($this->baling));

    expect($this->baling->fresh()->trashed())->toBeTrue();
});

test('the delete check runs no query per row', function (): void {
    $countQueries = function (): int {
        Livewire::withoutLazyLoading();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(DistrictDataTable::class);

        return count(DB::getQueryLog());
    };

    District::factory()->for($this->kedah)->create();
    // The first render also loads the permissions of the user.
    $countQueries();
    $queriesForThreeRows = $countQueries();

    District::factory()->count(7)->create();

    expect($countQueries())->toBe($queriesForThreeRows);
});

test('the table hides delete exactly when the delete action refuses it', function (Closure $arrange, bool $blocked): void {
    $district = $arrange();
    $delete = TestAction::make('delete')->table($district);
    $table = Livewire::test(DistrictDataTable::class);

    if ($blocked) {
        $table->assertActionHidden($delete);
    } else {
        $table->assertActionVisible($delete);
    }

    $refused = false;

    try {
        DeleteDistrict::make()->execute($district);
    } catch (LogicException) {
        $refused = true;
    }

    expect($refused)->toBe($blocked)
        ->and($district->fresh()->trashed())->toBe(! $blocked);
})->with([
    'a live subdistrict' => [fn (): District => Subdistrict::factory()->create()->district, true],
    'only a deleted subdistrict' => [function (): District {
        $child = Subdistrict::factory()->create();
        $child->delete();

        return $child->district;
    }, false],
    'a live address' => [function (): District {
        $district = District::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['district_id' => $district->id]);

        return $district;
    }, true],
    'only a deleted address' => [function (): District {
        $district = District::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['district_id' => $district->id])->delete();

        return $district;
    }, false],
    'the locality' => [function (): District {
        config()->set('address.locality.district', '0101');

        return District::query()->where('code', '0101')->whereRelation('state', 'code', '01')->sole();
    }, true],
]);
