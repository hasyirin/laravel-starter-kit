<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Livewire\Component;
use App\Livewire\Components\Navigation\Group;
use App\Livewire\Components\Navigation\Item;
use App\Livewire\Components\Navigation\Spacer;
use App\Livewire\Components\Navigation\Style;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;

abstract class Navigation extends Component
{
    #[Locked]
    public Style $style = Style::Sidebar;

    /** @return array<Group|Spacer|Item> */
    abstract protected function items(): array;

    public function render(): View
    {
        return view("livewire.components.navigation.{$this->style->value}", [
            'entries' => collect($this->items())
                ->chunkWhile(fn (Group|Spacer|Item $entry): bool => ! $entry instanceof Spacer)
                ->map(fn (Collection $chunk): Collection => $chunk->filter(fn (Group|Spacer|Item $entry): bool => ! $entry instanceof Spacer && $entry->accessible))
                ->filter(fn (Collection $chunk): bool => $chunk->isNotEmpty())
                ->values()
                ->flatMap(fn (Collection $chunk, int $index): array => $index === 0 ? $chunk->all() : [Spacer::make(), ...$chunk]),
        ]);
    }
}
