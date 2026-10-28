<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\Tabs;

use App\Livewire\Components\Tab;

class CountingTabsHost extends TabsHost
{
    public static int $tabsCalls = 0;

    /**
     * @return list<Tab>
     */
    public function tabs(): array
    {
        self::$tabsCalls++;

        return parent::tabs();
    }

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
