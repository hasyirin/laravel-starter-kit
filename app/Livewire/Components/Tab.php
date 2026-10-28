<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use Closure;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;
use LogicException;

final class Tab
{
    private ?string $label = null;

    private ?string $title = null;

    private ?string $description = null;

    /**
     * @var (Closure(): ?string)|string|null
     */
    private Closure|string|null $icon = null;

    /**
     * @var (Closure(): (int|string|null))|int|string|null
     */
    private Closure|int|string|null $badge = null;

    private ?string $badgeColor = null;

    /**
     * @var (Closure(): ?string)|string|null
     */
    private Closure|string|null $tooltipAlways = null;

    /**
     * @var (Closure(): ?string)|string|null
     */
    private Closure|string|null $tooltipDisabled = null;

    /**
     * @var (Closure(): bool)|bool
     */
    private Closure|bool $hidden = false;

    /**
     * @var (Closure(): bool)|bool
     */
    private Closure|bool $disabled = false;

    /**
     * @var (Closure(): mixed)|View|string|null
     */
    private Closure|View|string|null $content = null;

    /**
     * @var (Closure(): array<string, mixed>)|array<string, mixed>
     */
    private Closure|array $params = [];

    private bool $keepAlive = false;

    private bool $lazy = false;

    private ?string $url = null;

    private bool $isDefault = false;

    private function __construct(private readonly string $slug) {}

    public static function make(string $slug): self
    {
        if ($slug === '') {
            throw new InvalidArgumentException('A tab slug cannot be empty.');
        }

        if (json_validate($slug) && ! is_array(json_decode($slug, true))) {
            throw new InvalidArgumentException("A tab slug cannot be a JSON value such as a number or a boolean. Got [{$slug}].");
        }

        return new self($slug);
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param  (Closure(): ?string)|string|null  $icon
     */
    public function icon(Closure|string|null $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param  (Closure(): (int|string|null))|int|string|null  $badge
     */
    public function badge(Closure|int|string|null $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    public function badgeColor(?string $badgeColor): self
    {
        $this->badgeColor = $badgeColor;

        return $this;
    }

    /**
     * @param  (Closure(): ?string)|string|null  $always
     * @param  (Closure(): ?string)|string|null  $disabled
     */
    public function tooltip(Closure|string|null $always = null, Closure|string|null $disabled = null): self
    {
        $this->tooltipAlways = $always;
        $this->tooltipDisabled = $disabled;

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

    /**
     * @param  (Closure(): bool)|bool  $disabled
     */
    public function disabled(Closure|bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * @param  (Closure(): mixed)|View|string  $content
     */
    public function content(Closure|View|string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @param  (Closure(): array<string, mixed>)|array<string, mixed>  $params
     */
    public function params(Closure|array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function keepAlive(bool $keepAlive = true): self
    {
        $this->keepAlive = $keepAlive;

        return $this;
    }

    public function lazy(bool $lazy = true): self
    {
        $this->lazy = $lazy;

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function markDefault(bool $isDefault = true): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function resolvedLabel(): string
    {
        return $this->label ?? $this->slug;
    }

    public function resolvedTitle(): ?string
    {
        return $this->title;
    }

    public function resolvedDescription(): ?string
    {
        return $this->description;
    }

    public function resolvedIcon(): ?string
    {
        return value($this->icon);
    }

    public function resolvedBadge(): ?string
    {
        $badge = value($this->badge);

        return $badge === null ? null : (string) $badge;
    }

    public function resolvedBadgeColor(): ?string
    {
        return $this->badgeColor;
    }

    public function resolvedTooltip(bool $isDisabled): ?string
    {
        $tooltip = $isDisabled && $this->tooltipDisabled !== null ? $this->tooltipDisabled : $this->tooltipAlways;

        return value($tooltip);
    }

    public function isVisible(): bool
    {
        return ! value($this->hidden);
    }

    public function isDisabled(): bool
    {
        return (bool) value($this->disabled);
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isKeptAlive(): bool
    {
        return $this->keepAlive;
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }

    public function resolvedUrl(): ?string
    {
        return $this->url;
    }

    public function resolvedContent(): string|View
    {
        if ($this->content === null) {
            throw new LogicException("Tab [{$this->slug}] has no content.");
        }

        $content = value($this->content);

        if ($content instanceof View) {
            if ($this->keepAlive) {
                throw new LogicException("Tab [{$this->slug}] cannot keep a view alive. Use a Livewire component.");
            }

            return $content;
        }

        if (is_string($content) && is_subclass_of($content, Component::class)) {
            return $content;
        }

        $description = is_string($content) ? $content : get_debug_type($content);

        throw new LogicException("Tab [{$this->slug}] content [{$description}] is not a view or a Livewire component class.");
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedParams(): array
    {
        return value($this->params);
    }
}
