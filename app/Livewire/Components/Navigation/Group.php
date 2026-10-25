<?php

declare(strict_types=1);

namespace App\Livewire\Components\Navigation;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

final class Group implements Htmlable
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

    public function toHtml(): string
    {
        return view('partials.navigation.group', ['group' => $this])->render();
    }

    /**
     * @param  Arrayable<array-key, Item>|array<array-key, Item>  $items
     */
    public static function make(?string $label = null, Arrayable|array $items = []): self
    {
        return new self($label, $items);
    }
}
