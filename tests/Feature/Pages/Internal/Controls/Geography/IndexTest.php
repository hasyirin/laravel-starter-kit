<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use App\Livewire\DataTables\Internal\Geography\CountryDataTable;
use App\Livewire\DataTables\Internal\Geography\DistrictDataTable;
use App\Livewire\DataTables\Internal\Geography\PostOfficeDataTable;
use App\Livewire\DataTables\Internal\Geography\StateDataTable;
use App\Livewire\Layouts\Internal\Navigation;
use App\Livewire\Pages\Internal\Controls\Geography\Index;
use App\Livewire\Pages\Internal\Controls\Geography\Postcodes;
use App\Livewire\Pages\Internal\Controls\Geography\Subdistricts;
use App\Livewire\Pages\Internal\Controls\Geography\Tabs;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    grantPermissions($this->actor, 'internal.access');
    $this->actingAs($this->actor);
});

test('the geography page needs view-any', function (): void {
    $this->get(route('internal.controls.geography.index'))->assertForbidden();

    grantPermissions($this->actor, 'internal.controls.geography.view-any');

    $this->get(route('internal.controls.geography.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class)
        ->assertSee('<title>'.__('geography/geography.title').' - '.config('app.name').'</title>', false);
});

test('the geography page shows its six tabs in order, the countries first', function (): void {
    grantPermissions($this->actor, 'internal.controls.geography.view-any');

    Livewire::withoutLazyLoading()
        ->test(Tabs::class)
        ->assertSeeInOrder([
            __('geography/geography.tabs.countries'),
            __('geography/geography.tabs.states'),
            __('geography/geography.tabs.districts'),
            __('geography/geography.tabs.subdistricts'),
            __('geography/geography.tabs.cities'),
            __('geography/geography.tabs.postcodes'),
        ])
        ->assertSeeLivewire(CountryDataTable::class);
});

test('each geography tab shows its table', function (string $tab, string $content): void {
    grantPermissions($this->actor, 'internal.controls.geography.view-any');

    Livewire::withoutLazyLoading()
        ->withQueryParams(['tab' => $tab])
        ->test(Tabs::class)
        ->assertSeeLivewire($content);
})->with([
    'states' => ['states', StateDataTable::class],
    'districts' => ['districts', DistrictDataTable::class],
    'subdistricts' => ['subdistricts', Subdistricts::class],
    'cities' => ['cities', PostOfficeDataTable::class],
    'postcodes' => ['postcodes', Postcodes::class],
]);

test('the navigation shows the geography item under controls only with view-any', function (): void {
    Livewire::test(Navigation::class)->assertDontSee(route('internal.controls.geography.index'));

    grantPermissions($this->actor, 'internal.controls.geography.view-any', 'internal.controls.organizations.view-any');

    Livewire::test(Navigation::class)->assertSeeInOrder([__('navigation.controls'), route('internal.controls.organizations.index'), route('internal.controls.geography.index')], false);
});
