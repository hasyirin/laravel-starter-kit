<?php

declare(strict_types=1);

namespace App\Livewire\Components\Navigation;

use App\Domain\User\Models\User;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

final class Item
{
    /**
     * @var Collection<int, Item>
     */
    public Collection $children;

    public bool $accessible;

    public readonly string|int|null $badge;

    /**
     * @param  array<string, mixed>  $parameters
     * @param  (Closure(): bool)|array{0: string, 1?: mixed}|string|bool  $accessible
     * @param  Arrayable<array-key, Item>|array<array-key, Item>  $children
     */
    public function __construct(
        public readonly string $label,
        public readonly ?string $icon = null,
        public readonly ?string $route = null,
        public readonly array $parameters = [],
        public readonly ?string $active = null,
        public readonly ?string $href = null,
        Closure|array|string|bool $accessible = true,
        string|int|null $badge = null,
        Arrayable|array $children = [],
    ) {
        $allChildren = collect($children)->ensure(self::class);
        $this->children = $allChildren->filter(fn (self $child): bool => $child->accessible)->values();

        /** @var User|null $user */
        $user = auth()->user();

        $this->accessible = match (true) {
            $allChildren->isNotEmpty() && $this->children->isEmpty() => false,
            is_array($accessible) => $user?->can(...$accessible) ?? false,
            is_string($accessible) => $user?->can($accessible) ?? false,
            $accessible instanceof Closure => ($accessible)(),
            default => $accessible,
        };

        $this->badge = empty($badge) ? null : $badge;
    }

    public function active(): bool
    {
        if ($this->children->isNotEmpty()) {
            return $this->children->contains(fn (self $child): bool => $child->active());
        }

        return request()->routeIs($this->active ?? $this->route)
            && collect($this->parameters)->every(fn (mixed $value, string $key): bool => request()->route($key) === $value);
    }

    public function href(): ?string
    {
        return $this->route ? route($this->route, $this->parameters) : $this->href;
    }

    public function hasRoute(): bool
    {
        return filled($this->route);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  (Closure(): bool)|array{0: string, 1?: mixed}|string|bool  $accessible
     * @param  Arrayable<array-key, Item>|array<array-key, Item>  $children
     */
    public static function make(
        string $label,
        ?string $icon = null,
        ?string $route = null,
        array $parameters = [],
        ?string $active = null,
        ?string $href = null,
        Closure|array|string|bool $accessible = true,
        string|int|null $badge = null,
        Arrayable|array $children = [],
    ): self {
        return new self($label, $icon, $route, $parameters, $active, $href, $accessible, $badge, $children);
    }

    public function render(Style $style): View
    {
        return view("partials.navigation.{$style->value}.item", ['item' => $this, 'style' => $style]);
    }
}
