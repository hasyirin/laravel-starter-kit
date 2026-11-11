<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteState;
use App\Domain\Shared\Geography\Models\Address;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\StateDataTable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    config()->set('address.locality', ['country' => 'MYS', 'state' => '10', 'district' => null]);
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view-any', 'internal.controls.geography.create', 'internal.controls.geography.delete');
    $this->actingAs($this->actor);
    $this->malaysia = Country::factory()->create(['code' => 'MYS', 'name' => 'Malaysia']);
    $this->johor = State::factory()->for($this->malaysia)->create(['code' => '01', 'name' => 'Johor']);
    $this->selangor = State::factory()->for($this->malaysia)->create(['code' => '10', 'name' => 'Selangor']);
    $this->belait = State::factory()->for(Country::factory()->create(['code' => 'BRN', 'name' => 'Brunei']))->create(['code' => '01', 'name' => 'Belait']);
});

test('the index tab lists the states of every country with their country', function (): void {
    Livewire::test(StateDataTable::class)
        ->assertCanSeeTableRecords([$this->johor, $this->selangor, $this->belait])
        ->assertTableColumnVisible('country.name')
        ->assertTableColumnStateSet('country.name', 'Brunei', $this->belait);
});

test('the index tab puts the local state first, also when the user sorts by name', function (): void {
    Livewire::test(StateDataTable::class)
        ->assertCanSeeTableRecords([$this->selangor, $this->belait, $this->johor], inOrder: true)
        ->sortTable('name', 'desc')
        ->assertCanSeeTableRecords([$this->selangor, $this->johor, $this->belait], inOrder: true);
});

test('the index tab searches by country and filters by country', function (): void {
    Livewire::test(StateDataTable::class)
        ->searchTable('Brunei')
        ->assertCanSeeTableRecords([$this->belait])
        ->assertCanNotSeeTableRecords([$this->johor, $this->selangor])
        ->searchTable()
        ->filterTable('country', $this->malaysia->id)
        ->assertCanSeeTableRecords([$this->johor, $this->selangor])
        ->assertCanNotSeeTableRecords([$this->belait]);
});

test('the index tab has no create action', function (): void {
    Livewire::test(StateDataTable::class)->assertActionHidden(TestAction::make('create')->table());
});

test("the country tab lists only that country's states, the local state first", function (): void {
    Livewire::test(StateDataTable::class, ['country' => $this->malaysia])
        ->assertCanSeeTableRecords([$this->selangor, $this->johor], inOrder: true)
        ->assertCanNotSeeTableRecords([$this->belait])
        ->assertTableColumnHidden('country.name');
});

test('the country tab needs view on the country', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.view-any');

    Livewire::test(StateDataTable::class, ['country' => $this->malaysia])->assertForbidden();
});

test('the create action of the country tab goes to the create page', function (): void {
    Livewire::test(StateDataTable::class, ['country' => $this->malaysia])
        ->callAction(TestAction::make('create')->table())
        ->assertRedirect(route('internal.controls.geography.countries.states.create', $this->malaysia));
});

test('the create action of the country tab is hidden without create', function (): void {
    revokePermissions($this->actor, 'internal.controls.geography.create');

    Livewire::test(StateDataTable::class, ['country' => $this->malaysia])->assertActionHidden(TestAction::make('create')->table());
});

test('the row click opens the state under its country', function (): void {
    Livewire::test(StateDataTable::class)
        ->callAction(TestAction::make('view')->table($this->belait))
        ->assertRedirect(route('internal.controls.geography.countries.states.show', [$this->belait->country, $this->belait]));
});

test('the delete action is hidden on the local state and on a state with districts', function (): void {
    District::factory()->for($this->johor)->create();

    Livewire::test(StateDataTable::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->selangor))
        ->assertActionHidden(TestAction::make('delete')->table($this->johor))
        ->callAction(TestAction::make('delete')->table($this->belait));

    expect($this->belait->fresh()->trashed())->toBeTrue();
});

test('the delete check runs no query per row', function (): void {
    config()->set('address.locality.state', '99');
    $countQueries = function (): int {
        Livewire::withoutLazyLoading();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(StateDataTable::class);

        return count(DB::getQueryLog());
    };

    // The first render also loads the permissions of the user.
    $countQueries();
    $queriesForThreeRows = $countQueries();

    State::factory()->count(7)->create();

    expect($countQueries())->toBe($queriesForThreeRows);
});

test('the table hides delete exactly when the delete action refuses it', function (Closure $arrange, bool $blocked): void {
    $state = $arrange();
    $delete = TestAction::make('delete')->table($state);
    $table = Livewire::test(StateDataTable::class);

    if ($blocked) {
        $table->assertActionHidden($delete);
    } else {
        $table->assertActionVisible($delete);
    }

    $refused = false;

    try {
        DeleteState::make()->execute($state);
    } catch (LogicException) {
        $refused = true;
    }

    expect($refused)->toBe($blocked)
        ->and($state->fresh()->trashed())->toBe(! $blocked);
})->with([
    'a live district' => [fn (): State => District::factory()->create()->state, true],
    'only live cities' => [fn (): State => PostOffice::factory()->create()->state, true],
    'only a deleted district and a deleted city' => [function (): State {
        $state = State::factory()->create();
        District::factory()->for($state)->create()->delete();
        PostOffice::factory()->for($state)->create()->delete();

        return $state;
    }, false],
    'a live address' => [function (): State {
        $state = State::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['state_id' => $state->id]);

        return $state;
    }, true],
    'only a deleted address' => [function (): State {
        $state = State::factory()->create();
        Address::factory()->for(Organization::factory(), 'addressable')->create(['state_id' => $state->id])->delete();

        return $state;
    }, false],
    'the locality' => [fn (): State => State::query()->where('code', '10')->whereRelation('country', 'code', 'MYS')->sole(), true],
]);
