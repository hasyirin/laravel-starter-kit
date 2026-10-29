@props([
    'panels',
    'currentTab' => null,
    'orientation' => 'horizontal',
    'params' => [],
])

@php
    $animation = $orientation === 'vertical' ? 'animate-tab-enter-y' : 'animate-tab-enter-x';
    $keptAliveValues = collect($panels)
        ->filter(fn (App\Livewire\Components\Tab $tab): bool => $tab->isKeptAlive())
        ->flatMap(fn (App\Livewire\Components\Tab $tab): array => $tab->isDefault() ? [$tab->slug(), ''] : [$tab->slug()])
        ->map(fn (string $value): string => Js::from($value)->toHtml());
@endphp

<div {{ $attributes->class('transition-opacity') }} data-tab-content wire:loading.class="opacity-50" wire:target="switchTab">
    @foreach($panels as $tab)
        @php
            $slug = $tab->slug();
            $content = $tab->resolvedContent();
            $mergedParams = array_merge($tab->resolvedParams(), $params);
            $showWhen = match (true) {
                $tab->isKeptAlive() => '$wire.tab === '.Js::from($slug).($tab->isDefault() ? " || \$wire.tab === ''" : ''),
                $keptAliveValues->isNotEmpty() => '! ['.$keptAliveValues->implode(', ').'].includes($wire.tab)',
                default => null,
            };
        @endphp

        <div
            wire:key="tab-content-{{ $slug }}"
            data-tab-panel="{{ $slug }}"
            @class([$animation, 'motion-reduce:animate-none'])
            @if($showWhen !== null) x-show="{{ $showWhen }}" @endif
            @if($slug !== $currentTab?->slug()) style="display: none" @endif
        >
            @if($content instanceof Illuminate\Contracts\View\View)
                {{ $content->with($mergedParams) }}
            @else
                @livewire($content, $tab->isLazy() ? [...$mergedParams, 'lazy' => true] : $mergedParams, key('tab-content-'.$slug))
            @endif
        </div>
    @endforeach
</div>
