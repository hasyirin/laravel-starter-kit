<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Domain\Shared\Common\Contracts\Enums\HasColor;
use App\Domain\Shared\Common\Contracts\Enums\HasIcon;
use App\Domain\Shared\Common\Contracts\Enums\HasLabel;
use BackedEnum;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

final class Option
{
    /**
     * @var (Closure(): (Htmlable|string))|Htmlable|string|null
     */
    private Closure|Htmlable|string|null $label = null;

    /**
     * @var (Closure(): ?string)|string|null
     */
    private Closure|string|null $selectedLabel = null;

    /**
     * @var (Closure(): ?string)|string|null
     */
    private Closure|string|null $description = null;

    /**
     * @var (Closure(): (Htmlable|string|null))|Htmlable|string|null
     */
    private Closure|Htmlable|string|null $icon = null;

    /**
     * @var (Closure(): ?string)|string|null
     */
    private Closure|string|null $badge = null;

    private ?string $color = null;

    /**
     * @var (Closure(): bool)|bool
     */
    private Closure|bool $disabled = false;

    /**
     * @var (Closure(): bool)|bool
     */
    private Closure|bool $hidden = false;

    private function __construct(private readonly BackedEnum|string|int|float $value) {}

    public static function make(BackedEnum|string|int|float $value): self
    {
        return new self($value);
    }

    /**
     * @param  iterable<array-key, self|BackedEnum|Htmlable|string|int|float|null>  $options
     * @return Collection<int, self>
     */
    public static function collect(iterable $options): Collection
    {
        return collect($options)
            ->map(fn (self|BackedEnum|Htmlable|string|int|float|null $label, int|string $value): self => match (true) {
                $label instanceof self => $label,
                $label instanceof BackedEnum => self::make($label),
                $label === null => self::make($value),
                default => self::make($value)->label($label instanceof Htmlable ? $label : (string) $label),
            })
            ->filter(fn (self $option): bool => $option->isVisible())
            ->values();
    }

    /**
     * @param  (Closure(): (Htmlable|string))|Htmlable|string  $label
     */
    public function label(Closure|Htmlable|string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param  (Closure(): ?string)|string|null  $selectedLabel
     */
    public function selectedLabel(Closure|string|null $selectedLabel): self
    {
        $this->selectedLabel = $selectedLabel;

        return $this;
    }

    /**
     * @param  (Closure(): ?string)|string|null  $description
     */
    public function description(Closure|string|null $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param  (Closure(): (Htmlable|string|null))|Htmlable|string|null  $icon
     */
    public function icon(Closure|Htmlable|string|null $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param  (Closure(): ?string)|string|null  $badge
     */
    public function badge(Closure|string|null $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    public function color(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @param  (Closure(): bool)|bool  $disabled
     */
    public function disabled(Closure|bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @param  (Closure(): bool)|bool  $hidden
     */
    public function hidden(Closure|bool $hidden = true): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @param  (Closure(): bool)|bool  $visible
     */
    public function visible(Closure|bool $visible = true): self
    {
        $this->hidden = $visible instanceof Closure ? fn (): bool => ! $visible() : ! $visible;

        return $this;
    }

    public function value(): string|int|float
    {
        return $this->value instanceof BackedEnum ? $this->value->value : $this->value;
    }

    public function resolvedLabel(): Htmlable|string
    {
        $label = $this->label instanceof Closure ? ($this->label)() : $this->label;

        return $label ?? ($this->value instanceof HasLabel ? $this->value->label() : (string) $this->value());
    }

    public function resolvedText(): string
    {
        $label = $this->resolvedLabel();

        return $label instanceof Htmlable ? html_entity_decode(strip_tags($label->toHtml()), ENT_QUOTES | ENT_HTML5) : $label;
    }

    public function resolvedSelectedLabel(): ?string
    {
        return $this->resolve($this->selectedLabel);
    }

    public function resolvedDescription(): ?string
    {
        return $this->resolve($this->description);
    }

    public function resolvedIcon(): Htmlable|string|null
    {
        $icon = $this->resolve($this->icon) ?? ($this->value instanceof HasIcon ? $this->value->icon() : null);

        return $icon instanceof BackedEnum ? (string) $icon->value : $icon;
    }

    public function resolvedBadge(): ?string
    {
        return $this->resolve($this->badge);
    }

    public function resolvedColor(): ?string
    {
        $color = $this->color ?? ($this->value instanceof HasColor ? $this->value->color() : null);

        return is_string($color) ? $color : null;
    }

    public function isDisabled(): bool
    {
        return $this->resolve($this->disabled);
    }

    public function isVisible(): bool
    {
        return ! $this->resolve($this->hidden);
    }

    /**
     * @template TValue
     *
     * @param  (Closure(): TValue)|TValue  $value
     * @return TValue
     */
    private function resolve(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }
}
