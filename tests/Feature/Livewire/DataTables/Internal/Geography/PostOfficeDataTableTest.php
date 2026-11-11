<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeletePostOffice;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\PostOfficeDataTable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    config()->set('address.locality', ['country' => 'MYS', 'state' => '02', 'district' => null]);
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view-any', 'internal.controls.geography.create', 'internal.controls.geography.delete');
    $this->actingAs($this->actor);
    $malaysia = Country::factory()->create(['code' => 'MYS', 'name' => 'Malaysia']);
    $this->johor = State::factory()->for($malaysia)->create(['code' => '01', 'name' => 'Johor']);
    $this->kedah = State::factory()->for($malaysia)->create(['code' => '02', 'name' => 'Kedah']);
    $this->muar = PostOffice::factory()->for($this->johor)->create(['name' => 'Muar']);
    $this->alorSetar = PostOffice::factory()->for($this->kedah)->create(['name' => 'Alor Setar']);
    $this->yan = PostOffice::factory()->for($this->kedah)->create(['name' => 'Yan']);
});

test('the index tab lists every city with its state and country, local cities first', function (): void {
    Livewire::test(PostOfficeDataTable::class)
        ->assertCanSeeTableRecords([$this->alorSetar, $this->yan, $this->muar], inOrder: true)
        ->assertTableColumnStateSet('state.name', 'Johor', $this->muar)
        ->assertTableColumnStateSet('state.country.name', 'Malaysia', $this->muar)
        ->assertActionHidden(TestAction::make('create')->table());
});

test('the index tab keeps local cities first when the user sorts', function (): void {
    Livewire::test(PostOfficeDataTable::class)
        ->sortTable('name', 'desc')
        ->assertCanSeeTableRecords([$this->yan, $this->alorSetar, $this->muar], inOrder: true);
});

test('the index tab filters by state', function (): void {
    Livewire::test(PostOfficeDataTable::class)
        ->filterTable('state', $this->johor->id)
        ->assertCanSeeTableRecords([$this->muar])
        ->assertCanNotSeeTableRecords([$this->alorSetar, $this->yan]);
});

test("the state tab lists only that state's cities with their postcode counts", function (): void {
    Postcode::factory()->for($this->yan)->count(2)->create();

    Livewire::test(PostOfficeDataTable::class, ['state' => $this->kedah])
        ->assertCanSeeTableRecords([$this->alorSetar, $this->yan])
        ->assertCanNotSeeTableRecords([$this->muar])
        ->assertTableColumnStateSet('postcodes_count', 2, $this->yan)
        ->assertTableColumnHidden('state.name');
});

test('the create action of the state tab goes to the create page', function (): void {
    Livewire::test(PostOfficeDataTable::class, ['state' => $this->kedah])
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('internal.controls.geography.countries.states.cities.create', [$this->kedah->country, $this->kedah]));
});

test('the create action of the state tab is hidden without create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    Livewire::test(PostOfficeDataTable::class, ['state' => $this->kedah])->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the city under its state', function (): void {
    Livewire::test(PostOfficeDataTable::class)
        ->callAction(TestAction::make('view')->table($this->muar))
        ->assertRedirect(route('internal.controls.geography.countries.states.cities.show', [$this->johor->country, $this->johor, $this->muar]));
});

test('the delete action is hidden on a city with postcodes or addresses', function (): void {
    Postcode::factory()->for($this->yan)->create();
    Address::factory()->for(Organization::factory(), 'addressable')->create(['post_office_id' => $this->muar->id]);

    Livewire::test(PostOfficeDataTable::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->yan))
        ->assertActionHidden(TestAction::make('delete')->table($this->muar))
        ->callAction(TestAction::make('delete')->table($this->alorSetar));

    expect($this->alorSetar->fresh()->trashed())->toBeTrue();
});

test('the delete check runs no query per row', function (): void {
    $countQueries = function (): int {
        Livewire::withoutLazyLoading();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(PostOfficeDataTable::class);

        return count(DB::getQueryLog());
    };

    // The first render also loads the permissions of the user.
    $countQueries();
    $queriesForThreeRows = $countQueries();

    PostOffice::factory()->count(7)->create();

    expect($countQueries())->toBe($queriesForThreeRows);
});

test('the table hides delete exactly when the delete action refuses it', function (Closure $arrange, bool $blocked): void {
    $postOffice = $arrange();
    $delete = TestAction::make('delete')->table($postOffice);
    $table = Livewire::test(PostOfficeDataTable::class);

    if ($blocked) {
        $table->assertActionHidden($delete);
    } else {
        $table->assertActionVisible($delete);
    }

    $refused = false;

    try {
        DeletePostOffice::make()->execute($postOffice);
    } catch (LogicException) {
        $refused = true;
    }

    expect($refused)->toBe($blocked)
        ->and($postOffice->fresh()->trashed())->toBe(! $blocked);
})->with([
    'a live postcode' => [fn (): PostOffice => Postcode::factory()->create()->postOffice, true],
    'only a deleted postcode' => [function (): PostOffice {
        $child = Postcode::factory()->create();
        $child->delete();

        return $child->postOffice;
    }, false],
    'a live address' => [function (): PostOffice {
        $postOffice = PostOffice::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['post_office_id' => $postOffice->id]);

        return $postOffice;
    }, true],
    'only a deleted address' => [function (): PostOffice {
        $postOffice = PostOffice::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['post_office_id' => $postOffice->id])->delete();

        return $postOffice;
    }, false],
]);
