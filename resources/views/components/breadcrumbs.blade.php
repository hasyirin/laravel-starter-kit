@props([
    'items' => [],
    'home' => null,
    'max' => 4,
])

@php
    $items = array_values(array_filter($items));
    $home = $home === false ? null : ($home ?? route('home'));
    $hiddenItems = count($items) > $max ? array_slice($items, 0, -2) : [];
    $shownItems = $hiddenItems === [] ? $items : array_slice($items, -2);
    $currentItem = array_pop($shownItems);
@endphp

@if($currentItem !== null)
    <nav aria-label="{{ __('navigation.breadcrumbs.label') }}" {{ $attributes }}>
        <flux:breadcrumbs class="flex-wrap">
            @if($home !== null)
                <flux:breadcrumbs.item icon="home" :href="$home" wire:navigate aria-label="{{ __('navigation.breadcrumbs.home') }}" />
            @endif

            @if($hiddenItems !== [])
                <flux:breadcrumbs.item>
                    <flux:dropdown>
                        <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" aria-label="{{ __('navigation.breadcrumbs.more') }}" />

                        <flux:navmenu>
                            @foreach($hiddenItems as $item)
                                <flux:navmenu.item
                                    :href="$item['href'] ?? null"
                                    :wire:navigate="filled($item['href'] ?? null)"
                                    :disabled="blank($item['href'] ?? null)"
                                    :aria-disabled="blank($item['href'] ?? null) ? 'true' : null"
                                >
                                    {{ $item['label'] }}
                                </flux:navmenu.item>
                            @endforeach
                        </flux:navmenu>
                    </flux:dropdown>
                </flux:breadcrumbs.item>
            @endif

            @foreach($shownItems as $item)
                <flux:breadcrumbs.item :href="$item['href'] ?? null" :wire:navigate="filled($item['href'] ?? null)">{{ $item['label'] }}</flux:breadcrumbs.item>
            @endforeach

            <flux:breadcrumbs.item aria-current="page">{{ $currentItem['label'] }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </nav>
@endif
