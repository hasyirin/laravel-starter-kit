<?php

declare(strict_types=1);

namespace App\Livewire\Components\Navigation;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

final class Group
{
    /**
     * @var Collection<int, Item>
     */
    public Collection $items;

    public bool $accessible;

    /**
     * @param  Arrayable<array-key, Item>|array<array-key, Item>  $items
     */
    public function __construct(
        public readonly ?string $label = null,
        Arrayable|array $items = [],
    ) {
        $this->items = collect($items)->ensure(Item::class)->filter(fn (Item $item): bool => $item->accessible)->values();
        $this->accessible = $this->items->isNotEmpty();
    }

    public function active(): bool
    {
        return $this->items->contains(fn (Item $item): bool => $item->active());
    }

    public function render(Style $style): View
    {
        return view("partials.navigation.{$style->value}.group", ['group' => $this, 'style' => $style]);
    }

    /**
     * @param  Arrayable<array-key, Item>|array<array-key, Item>  $items
     */
    public static function make(?string $label = null, Arrayable|array $items = []): self
    {
        return new self($label, $items);
    }
}
