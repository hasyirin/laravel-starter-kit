<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\PostcodeDataTable;
use App\Livewire\Pages\Internal\Controls\Geography\PostcodeModal;
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
    $this->muar = PostOffice::factory()->for(State::factory()->for($malaysia)->create(['code' => '01', 'name' => 'Johor']))->create(['name' => 'Muar']);
    $this->alorSetar = PostOffice::factory()->for(State::factory()->for($malaysia)->create(['code' => '02', 'name' => 'Kedah']))->create(['name' => 'Alor Setar']);
    $this->muarCode = Postcode::factory()->for($this->muar)->create(['code' => '84000']);
    $this->alorSetarCode = Postcode::factory()->for($this->alorSetar)->create(['code' => '05000']);
    $this->alorSetarOther = Postcode::factory()->for($this->alorSetar)->create(['code' => '05400']);
});

test('the index tab lists every postcode with its city and state, local postcodes first, also when the user sorts by code', function (): void {
    Livewire::test(PostcodeDataTable::class)
        ->assertCanSeeTableRecords([$this->alorSetarCode, $this->alorSetarOther, $this->muarCode], inOrder: true)
        ->sortTable('code', 'desc')
        ->assertCanSeeTableRecords([$this->alorSetarOther, $this->alorSetarCode, $this->muarCode], inOrder: true)
        ->assertTableColumnStateSet('postOffice.name', 'Muar', $this->muarCode)
        ->assertTableColumnStateSet('postOffice.state.name', 'Johor', $this->muarCode)
        ->assertTableColumnStateSet('postOffice.state.country.name', 'Malaysia', $this->muarCode)
        ->assertActionHidden(TestAction::make('create')->table());
});

test('the search matches the start of the postcode', function (): void {
    Livewire::test(PostcodeDataTable::class)
        ->searchTable('054')
        ->assertCanSeeTableRecords([$this->alorSetarOther])
        ->assertCanNotSeeTableRecords([$this->alorSetarCode, $this->muarCode])
        ->searchTable('000')
        ->assertCanNotSeeTableRecords([$this->alorSetarCode, $this->alorSetarOther, $this->muarCode]);
});

test('the search finds an upper-case postcode by a lower-case term', function (): void {
    // PostgreSQL matches LIKE with case, but SQLite ignores case unless this is on.
    DB::statement('PRAGMA case_sensitive_like = ON');

    try {
        $postcode = Postcode::factory()->for($this->muar)->create(['code' => 'AB1']);

        Livewire::test(PostcodeDataTable::class)
            ->searchTable(' ab')
            ->assertCanSeeTableRecords([$postcode])
            ->assertCanNotSeeTableRecords([$this->muarCode]);
    } finally {
        DB::statement('PRAGMA case_sensitive_like = OFF');
    }
});

test('the search finds a code with an underscore, and the underscore is no wildcard', function (): void {
    $underscore = Postcode::factory()->for($this->muar)->create(['code' => 'A_1']);
    $letter = Postcode::factory()->for($this->muar)->create(['code' => 'AB1']);

    Livewire::test(PostcodeDataTable::class)
        ->searchTable('a_')
        ->assertCanSeeTableRecords([$underscore])
        ->assertCanNotSeeTableRecords([$letter]);
});

test('the search treats a percent sign and an exclamation mark as plain characters', function (): void {
    $percent = Postcode::factory()->for($this->muar)->create(['code' => '0%0']);
    $exclamation = Postcode::factory()->for($this->muar)->create(['code' => '0!0']);

    Livewire::test(PostcodeDataTable::class)
        ->searchTable('0%')
        ->assertCanSeeTableRecords([$percent])
        ->assertCanNotSeeTableRecords([$exclamation, $this->alorSetarCode, $this->muarCode])
        ->searchTable('0!')
        ->assertCanSeeTableRecords([$exclamation])
        ->assertCanNotSeeTableRecords([$percent]);
});

test('the search keeps a space inside the code', function (): void {
    $spaced = Postcode::factory()->for($this->muar)->create(['code' => 'AB 1']);
    $other = Postcode::factory()->for($this->muar)->create(['code' => 'AB2']);

    Livewire::test(PostcodeDataTable::class)
        ->searchTable('ab 1')
        ->assertCanSeeTableRecords([$spaced])
        ->assertCanNotSeeTableRecords([$other]);
});

test("the city tab lists only that city's postcodes", function (): void {
    Livewire::test(PostcodeDataTable::class, ['postOffice' => $this->alorSetar])
        ->assertCanSeeTableRecords([$this->alorSetarCode, $this->alorSetarOther])
        ->assertCanNotSeeTableRecords([$this->muarCode])
        ->assertTableColumnHidden('postOffice.name');
});

test('the create action of the city tab opens the postcode modal', function (): void {
    Livewire::test(PostcodeDataTable::class, ['postOffice' => $this->alorSetar])
        ->callAction(TestAction::make('create')->table())
        ->assertDispatchedTo(PostcodeModal::class, 'show');
});

test('the create action of the city tab is hidden without create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    Livewire::test(PostcodeDataTable::class, ['postOffice' => $this->alorSetar])->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the postcode in the modal', function (): void {
    Livewire::test(PostcodeDataTable::class)
        ->callAction(TestAction::make('view')->table($this->muarCode))
        ->assertDispatchedTo(PostcodeModal::class, 'show', id: $this->muarCode->id);
});

test('the delete action deletes a postcode', function (): void {
    Livewire::test(PostcodeDataTable::class)
        ->callAction(TestAction::make('delete')->table($this->muarCode));

    expect($this->muarCode->fresh()->trashed())->toBeTrue();
});

test('the delete action is hidden without delete', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.delete');

    Livewire::test(PostcodeDataTable::class)->assertActionHidden(TestAction::make('delete')->table($this->muarCode));
});

test('the location shows the latitude and the longitude together, and is empty when one is missing', function (): void {
    $this->muarCode->update(['latitude' => 2.0442, 'longitude' => 102.5689]);
    $this->alorSetarCode->update(['latitude' => 6.1248, 'longitude' => null]);

    Livewire::test(PostcodeDataTable::class)
        ->assertTableColumnStateSet('location', '2.0442, 102.5689', $this->muarCode)
        ->assertTableColumnStateSet('location', null, $this->alorSetarCode);
});

test('the index tab sorts by city, by state and by country, with local postcodes still first', function (): void {
    $singaporeCode = Postcode::factory()->for(PostOffice::factory()->for(State::factory()->for(Country::factory()->create(['code' => 'SGP', 'name' => 'Singapore']))->create(['name' => 'Central']))->create(['name' => 'Bukit Merah']))->create(['code' => '15000']);
    $ipoh = PostOffice::factory()->for(State::factory()->for($this->muar->state->country)->create(['code' => '08', 'name' => 'Perak']))->create(['name' => 'Ipoh']);
    $ipohCode = Postcode::factory()->for($ipoh)->create(['code' => '30000']);
    $local = [$this->alorSetarCode, $this->alorSetarOther];

    Livewire::test(PostcodeDataTable::class)
        ->sortTable('postOffice.name')
        ->assertCanSeeTableRecords([...$local, $singaporeCode, $ipohCode, $this->muarCode], inOrder: true)
        ->sortTable('postOffice.name', 'desc')
        ->assertCanSeeTableRecords([...$local, $this->muarCode, $ipohCode, $singaporeCode], inOrder: true)
        ->sortTable('postOffice.state.name')
        ->assertCanSeeTableRecords([...$local, $singaporeCode, $this->muarCode, $ipohCode], inOrder: true)
        ->sortTable('postOffice.state.name', 'desc')
        ->assertCanSeeTableRecords([...$local, $ipohCode, $this->muarCode, $singaporeCode], inOrder: true)
        ->sortTable('postOffice.state.country.name', 'desc')
        ->assertCanSeeTableRecords([...$local, $singaporeCode], inOrder: true)
        ->sortTable('postOffice.state.country.name')
        ->assertCanSeeTableRecords([...$local, $this->muarCode], inOrder: true);
});
