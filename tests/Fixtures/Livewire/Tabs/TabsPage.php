<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\Tabs;

use Livewire\Attributes\Layout;

#[Layout('layouts.guest')]
class TabsPage extends TabsHost
{
    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-tabs :$tabs :$currentTab :query-as="$queryTabAs" />
                <x-tabs.content :panels="$tabPanels" :$currentTab />
            </div>
            BLADE;
    }
}
