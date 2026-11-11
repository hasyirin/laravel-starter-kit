<?php

declare(strict_types=1);

use App\Domain\Shared\Geography\Actions\DeleteState;
use App\Domain\Shared\Geography\Models\Country;
use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Domain\Shared\Geography\Models\State;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\AuditLogDataTable;
use App\Livewire\Pages\Internal\Controls\Geography\AuditEntryModal;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Livewire::withoutLazyLoading();
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create(['name' => 'Ada Lovelace']);
    grantPermissions($this->actor, 'internal.access', 'internal.controls.geography.view', 'internal.controls.geography.view-audit-log');
    $this->actingAs($this->actor);
});

test('the list shows the log of a record and its direct children, with who made each change', function (string $parentModel, string $childModel, string $foreignKey): void {
    $record = $parentModel::factory()->create();
    $child = $childModel::factory()->create([$foreignKey => $record->id]);
    $other = $childModel::factory()->create();
    $record->update(['name' => 'Renamed']);

    $onChild = Activity::query()->whereMorphedTo('subject', $child)->firstOrFail();
    $onOther = Activity::query()->whereMorphedTo('subject', $other)->firstOrFail();

    Livewire::test(AuditLogDataTable::class, ['record' => $record])
        ->assertCanSeeTableRecords([$onChild])
        ->assertCanNotSeeTableRecords([$onOther])
        ->assertSee('Ada Lovelace');
})->with([
    'states of a country' => [Country::class, State::class, 'country_id'],
    'districts of a state' => [State::class, District::class, 'state_id'],
    'cities of a state' => [State::class, PostOffice::class, 'state_id'],
    'subdistricts of a district' => [District::class, Subdistrict::class, 'district_id'],
    'postcodes of a city' => [PostOffice::class, Postcode::class, 'post_office_id'],
]);

test('the list calls a post office a city', function (string $parentModel, string $childModel, string $foreignKey): void {
    $record = $parentModel::factory()->create();
    $childModel::factory()->create([$foreignKey => $record->id]);

    Livewire::test(AuditLogDataTable::class, ['record' => $record])
        ->assertSee('City')
        ->assertDontSee('Post Office');
})->with([
    'a city of a state' => [State::class, PostOffice::class, 'state_id'],
    'a postcode of a city' => [PostOffice::class, Postcode::class, 'post_office_id'],
]);

test('the list names a postcode by its code', function (): void {
    $postOffice = PostOffice::factory()->create();
    $postcode = Postcode::factory()->for($postOffice)->create(['code' => '43000']);

    Livewire::test(AuditLogDataTable::class, ['record' => $postOffice])
        ->assertSee('Postcode 43000')
        ->assertDontSee('Postcode #'.$postcode->id);
});

test('the list names a deleted state', function (): void {
    $country = Country::factory()->create();
    $state = State::factory()->for($country)->create(['name' => 'Johor']);
    DeleteState::make()->execute($state);

    Livewire::test(AuditLogDataTable::class, ['record' => $country])
        ->assertSee('Johor');
});

test('the audit needs view-audit-log', function (string $component): void {
    revokePermissions($this->actor, 'internal.controls.geography.view-audit-log');

    Livewire::test($component, ['record' => Country::factory()->create()])->assertForbidden();
})->with([AuditLogDataTable::class, AuditEntryModal::class]);

test('a row click opens the modal with the entry', function (): void {
    $country = Country::factory()->create();
    $entry = Activity::query()->whereMorphedTo('subject', State::factory()->for($country)->create())->firstOrFail();

    Livewire::test(AuditLogDataTable::class, ['record' => $country])
        ->callAction(TestAction::make('view')->table($entry))
        ->assertDispatchedTo(AuditEntryModal::class, 'show', id: $entry->id);
});

test('the modal opens an entry of a state of the country', function (): void {
    $country = Country::factory()->create();
    $entry = Activity::query()->whereMorphedTo('subject', State::factory()->for($country)->create())->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['record' => $country])
        ->call('show', $entry->id)
        ->assertSet('showing', true);
});

test('the modal refuses an entry of another country', function (): void {
    $entry = Activity::query()->whereMorphedTo('subject', Country::factory()->create())->firstOrFail();

    Livewire::test(AuditEntryModal::class, ['record' => Country::factory()->create()])
        ->call('show', $entry->id)
        ->assertNotFound();
});
