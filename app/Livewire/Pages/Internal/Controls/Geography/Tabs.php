<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography;

use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\DataTables\Internal\Geography\CountryDataTable;
use App\Livewire\DataTables\Internal\Geography\DistrictDataTable;
use App\Livewire\DataTables\Internal\Geography\PostOfficeDataTable;
use App\Livewire\DataTables\Internal\Geography\StateDataTable;
use Illuminate\Contracts\View\View;

class Tabs extends Component
{
    use InteractsWithTabs;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('countries')
                ->label((string) __('geography/geography.tabs.countries'))
                ->content(CountryDataTable::class),
            Tab::make('states')
                ->label((string) __('geography/geography.tabs.states'))
                ->content(StateDataTable::class),
            Tab::make('districts')
                ->label((string) __('geography/geography.tabs.districts'))
                ->content(DistrictDataTable::class),
            Tab::make('subdistricts')
                ->label((string) __('geography/geography.tabs.subdistricts'))
                ->content(Subdistricts::class),
            Tab::make('cities')
                ->label((string) __('geography/geography.tabs.cities'))
                ->content(PostOfficeDataTable::class),
            Tab::make('postcodes')
                ->label((string) __('geography/geography.tabs.postcodes'))
                ->content(Postcodes::class),
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.tabs');
    }
}
