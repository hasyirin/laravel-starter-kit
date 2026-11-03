<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\Tabs;

class CardTabsHost extends TabsHost
{
    public string $variant = 'segmented';

    public ?bool $strip = null;

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                @if($strip === null)
                    <x-card.tabs :$tabs :$currentTab :panels="$tabPanels" :query-as="$queryTabAs" :$variant aria-label="Sections" class="tabs-test-class" id="tabs-test-id" />
                @else
                    <x-card.tabs :$tabs :$currentTab :panels="$tabPanels" :query-as="$queryTabAs" :$variant :$strip aria-label="Sections" class="tabs-test-class" id="tabs-test-id" />
                @endif
            </div>
            BLADE;
    }
}
