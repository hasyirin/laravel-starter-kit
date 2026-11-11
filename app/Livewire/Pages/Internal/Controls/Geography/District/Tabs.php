<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\District;

use App\Domain\Shared\Geography\Models\District;
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\Pages\Internal\Controls\Geography\Audit;
use App\Livewire\Pages\Internal\Controls\Geography\Subdistricts;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public District $district;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('subdistricts')
                ->label((string) __('geography/districts.tabs.subdistricts'))
                ->content(Subdistricts::class)
                ->params(['district' => $this->district]),
            Tab::make('audit')
                ->label((string) __('geography/districts.tabs.audit'))
                ->content(Audit::class)
                ->params(['record' => $this->district])
                ->visible(fn (): bool => $this->can('view', [$this->district, 'audit-log'])),
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
