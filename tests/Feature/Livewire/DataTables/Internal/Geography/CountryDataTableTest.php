<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteCountry;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\CountryDataTable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    config()->set('address.locality', ['country' => 'MYS', 'state' => null, 'district' => null]);
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view-any', 'internal.controls.geography.create', 'internal.controls.geography.delete');
    $this->actingAs($this->actor);
    $this->malaysia = Country::factory()->create(['code' => 'MYS', 'name' => 'Malaysia', 'alpha_2' => 'MY']);
    $this->brunei = Country::factory()->create(['code' => 'BRN', 'name' => 'Brunei', 'alpha_2' => 'BN']);
    $this->zambia = Country::factory()->create(['code' => 'ZMB', 'name' => 'Zambia', 'alpha_2' => 'ZM']);
});

test('the local country comes first, also when the user sorts by name', function (): void {
    Livewire::test(CountryDataTable::class)
        ->assertCanSeeTableRecords([$this->malaysia, $this->brunei, $this->zambia], inOrder: true)
        ->sortTable('name', 'desc')
        ->assertCanSeeTableRecords([$this->malaysia, $this->zambia, $this->brunei], inOrder: true);
});

test('the list shows the state count of each country', function (): void {
    State::factory()->for($this->brunei)->count(2)->create();

    Livewire::test(CountryDataTable::class)
        ->assertTableColumnStateSet('states_count', 2, $this->brunei)
        ->assertTableColumnStateSet('states_count', 0, $this->zambia);
});

test('the list searches by code, name, and alpha-2 code', function (): void {
    Livewire::test(CountryDataTable::class)
        ->searchTable('BRN')
        ->assertCanSeeTableRecords([$this->brunei])
        ->assertCanNotSeeTableRecords([$this->malaysia, $this->zambia])
        ->searchTable('Zambia')
        ->assertCanSeeTableRecords([$this->zambia])
        ->assertCanNotSeeTableRecords([$this->malaysia, $this->brunei])
        ->searchTable('MY')
        ->assertCanSeeTableRecords([$this->malaysia])
        ->assertCanNotSeeTableRecords([$this->brunei, $this->zambia]);
});

test('the table refuses a user without view-any', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.view-any');

    Livewire::test(CountryDataTable::class)->assertForbidden();
});

test('the create action goes to the create page', function (): void {
    Livewire::test(CountryDataTable::class)
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('internal.controls.geography.countries.create'));
});

test('the create action is hidden without create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    Livewire::test(CountryDataTable::class)->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the country', function (): void {
    Livewire::test(CountryDataTable::class)
        ->callAction(TestAction::make('view')->table($this->brunei))
        ->assertRedirect(route('internal.controls.geography.countries.show', $this->brunei));
});

test('the delete action deletes a country that nothing uses', function (): void {
    Livewire::test(CountryDataTable::class)
        ->callAction(TestAction::make('delete')->table($this->zambia));

    expect($this->zambia->fresh()->trashed())->toBeTrue();
});

test('the delete action is hidden on the local country and on a country with states', function (): void {
    State::factory()->for($this->brunei)->create();

    Livewire::test(CountryDataTable::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->malaysia))
        ->assertActionHidden(TestAction::make('delete')->table($this->brunei))
        ->assertActionVisible(TestAction::make('delete')->table($this->zambia));
});

test('the delete action is hidden without delete', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.delete');

    Livewire::test(CountryDataTable::class)->assertActionHidden(TestAction::make('delete')->table($this->zambia));
});

test('the delete check runs no query per row', function (): void {
    $countQueries = function (): int {
        Livewire::withoutLazyLoading();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(CountryDataTable::class);

        return count(DB::getQueryLog());
    };

    // The first render also loads the permissions of the user.
    $countQueries();
    $queriesForThreeRows = $countQueries();

    Country::factory()->count(7)->create();
    // Creating a country clears the cached local country.
    $countQueries();

    expect($countQueries())->toBe($queriesForThreeRows);
});

test('the table hides delete exactly when the delete action refuses it', function (Closure $arrange, bool $blocked): void {
    $country = $arrange();
    $delete = TestAction::make('delete')->table($country);
    $table = Livewire::test(CountryDataTable::class);

    if ($blocked) {
        $table->assertActionHidden($delete);
    } else {
        $table->assertActionVisible($delete);
    }

    $refused = false;

    try {
        DeleteCountry::make()->execute($country);
    } catch (LogicException) {
        $refused = true;
    }

    expect($refused)->toBe($blocked)
        ->and($country->fresh()->trashed())->toBe(! $blocked);
})->with([
    'a live state' => [fn (): Country => State::factory()->create()->country, true],
    'only a deleted state' => [function (): Country {
        $child = State::factory()->create();
        $child->delete();

        return $child->country;
    }, false],
    'a live address' => [function (): Country {
        $country = Country::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['country_id' => $country->id]);

        return $country;
    }, true],
    'only a deleted address' => [function (): Country {
        $country = Country::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['country_id' => $country->id])->delete();

        return $country;
    }, false],
    'the locality' => [fn (): Country => Country::query()->where('code', 'MYS')->sole(), true],
]);
