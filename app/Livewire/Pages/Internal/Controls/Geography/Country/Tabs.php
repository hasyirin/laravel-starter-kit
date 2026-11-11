<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\Country;

use App\Domain\Shared\Geography\Models\Country;
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\DataTables\Internal\Geography\StateDataTable;
use App\Livewire\Pages\Internal\Controls\Geography\Audit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public Country $country;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('states')
                ->label((string) __('geography/countries.tabs.states'))
                ->content(StateDataTable::class)
                ->params(['country' => $this->country]),
            Tab::make('audit')
                ->label((string) __('geography/countries.tabs.audit'))
                ->content(Audit::class)
                ->params(['record' => $this->country])
                ->visible(fn (): bool => $this->can('view', [$this->country, 'audit-log'])),
        ];
    }

    protected function rendersWithoutTabs(): bool
    {
        return true;
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.tabs');
    }
}
