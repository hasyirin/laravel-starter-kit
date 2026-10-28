<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\Tabs;

class CardTabsHost extends TabsHost
{
    public string $variant = 'segmented';

    public bool $strip = true;

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-card.tabs :$tabs :$currentTab :panels="$tabPanels" :query-as="$queryTabAs" :$variant :$strip aria-label="Sections" class="tabs-test-class" id="tabs-test-id" />
            </div>
            BLADE;
    }
}
