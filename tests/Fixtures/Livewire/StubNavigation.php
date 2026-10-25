<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Components\Navigation;
use App\Livewire\Components\Navigation\Group;
use App\Livewire\Components\Navigation\Item;
use App\Livewire\Components\Navigation\Spacer;

class StubNavigation extends Navigation
{
    /**
     * @var array<Group|Spacer|Item>
     */
    protected array $entries = [];

    /**
     * @param  array<Group|Spacer|Item>  $entries
     */
    public function mount(array $entries): void
    {
        $this->entries = $entries;
    }

    /**
     * @return array<Group|Spacer|Item>
     */
    protected function items(): array
    {
        return $this->entries;
    }
}
