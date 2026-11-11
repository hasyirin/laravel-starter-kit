<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\State;

use App\Domain\Shared\Geography\Models\State;
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\DataTables\Internal\Geography\DistrictDataTable;
use App\Livewire\DataTables\Internal\Geography\PostOfficeDataTable;
use App\Livewire\Pages\Internal\Controls\Geography\Audit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public State $state;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('districts')
                ->label((string) __('geography/states.tabs.districts'))
                ->content(DistrictDataTable::class)
                ->params(['state' => $this->state]),
            Tab::make('cities')
                ->label((string) __('geography/states.tabs.cities'))
                ->content(PostOfficeDataTable::class)
                ->params(['state' => $this->state]),
            Tab::make('audit')
                ->label((string) __('geography/states.tabs.audit'))
                ->content(Audit::class)
                ->params(['record' => $this->state])
                ->visible(fn (): bool => $this->can('view', [$this->state, 'audit-log'])),
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
