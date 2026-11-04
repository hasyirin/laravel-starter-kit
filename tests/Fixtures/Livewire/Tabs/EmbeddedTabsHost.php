<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\Tabs;

class EmbeddedTabsHost extends TabsHost
{
    protected function rendersWithoutTabs(): bool
    {
        return true;
    }
}
