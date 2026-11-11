<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography\PostOffice;

use App\Domain\Shared\Geography\Models\PostOffice;
use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;
use App\Livewire\Pages\Internal\Controls\Geography\Audit;
use App\Livewire\Pages\Internal\Controls\Geography\Postcodes;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Tabs extends Component
{
    use InteractsWithTabs;

    #[Locked]
    public PostOffice $postOffice;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('postcodes')
                ->label((string) __('geography/cities.tabs.postcodes'))
                ->content(Postcodes::class)
                ->params(['postOffice' => $this->postOffice]),
            Tab::make('audit')
                ->label((string) __('geography/cities.tabs.audit'))
                ->content(Audit::class)
                ->params(['record' => $this->postOffice])
                ->visible(fn (): bool => $this->can('view', [$this->postOffice, 'audit-log'])),
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
