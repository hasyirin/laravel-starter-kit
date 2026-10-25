<?php

declare(strict_types=1);

namespace App\Livewire\Components\Navigation;

use App\Domain\User\Models\User;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

final class Item implements Htmlable
{
    /**
     * @var Collection<int, Item>
     */
    public Collection $children;

    public bool $accessible;

    public readonly string|int|null $badge;

    /**
     * @param  (Closure(): bool)|array{0: string, 1?: mixed}|string|bool  $accessible
     * @param  Arrayable<array-key, Item>|array<array-key, Item>  $children
     */
    public function __construct(
        public readonly string $label,
        public readonly ?string $icon = null,
        public readonly ?string $route = null,
        public readonly ?string $active = null,
        public readonly ?string $href = null,
        Closure|array|string|bool $accessible = true,
        string|int|null $badge = null,
        Arrayable|array $children = [],
    ) {
        $allChildren = collect($children)->ensure(self::class);
        $this->children = $allChildren->filter(fn (self $child): bool => $child->accessible)->values();

        /** @var User $user */
        $user = auth()->user();

        $this->accessible = match (true) {
            $allChildren->isNotEmpty() && $this->children->isEmpty() => false,
            is_array($accessible) => $user->can(...$accessible),
            is_string($accessible) => $user->can($accessible),
            $accessible instanceof Closure => ($accessible)(),
            default => $accessible,
        };

        $this->badge = empty($badge) ? null : $badge;
    }

    public function active(): bool
    {
        return $this->children->isNotEmpty() ? $this->children->contains(fn (self $child): bool => $child->active()) : request()->routeIs($this->active ?? $this->route);
    }

    public function href(): ?string
    {
        return $this->route ? route($this->route) : $this->href;
    }

    public function hasRoute(): bool
    {
        return filled($this->route);
    }

    public function toHtml(): string
    {
        return view('partials.navigation.item', ['item' => $this])->render();
    }

    /**
     * @param  (Closure(): bool)|array{0: string, 1?: mixed}|string|bool  $accessible
     * @param  Arrayable<array-key, Item>|array<array-key, Item>  $children
     */
    public static function make(
        string $label,
        ?string $icon = null,
        ?string $route = null,
        ?string $active = null,
        ?string $href = null,
        Closure|array|string|bool $accessible = true,
        string|int|null $badge = null,
        Arrayable|array $children = [],
    ): self {
        return new self($label, $icon, $route, $active, $href, $accessible, $badge, $children);
    }
}
