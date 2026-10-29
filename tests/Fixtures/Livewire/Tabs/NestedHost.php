<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\Tabs;

use App\Livewire\Component;
use App\Livewire\Components\Tab;
use App\Livewire\Concerns\InteractsWithTabs;

class NestedHost extends Component
{
    use InteractsWithTabs;

    public function queryTabAs(): string
    {
        return 'holding';
    }

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        return [
            Tab::make('owner')->content(OwnerPanel::class),
            Tab::make('documents')->content(DocumentsPanel::class),
        ];
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
