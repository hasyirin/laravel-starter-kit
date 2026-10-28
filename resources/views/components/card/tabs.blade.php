@props([
    'tabs',
    'currentTab' => null,
    'panels',
    'queryAs' => 'tab',
    'variant' => 'segmented',
    'strip' => true,
    'orientation' => 'horizontal',
    'params' => [],
    'ariaLabel' => null,
])

@if($tabs !== [])
    <div @if($strip) data-tabs-strip @endif>
        @if($strip)
            <x-card @class(['rounded-b-none border-b-0', 'py-0!' => $variant === 'navbar', 'p-2!' => $variant !== 'navbar'])>
                @include('partials.tabs.card-bar')
            </x-card>
        @else
            <div class="mb-4">
                @include('partials.tabs.card-bar')
            </div>
        @endif

        <x-tabs.content :$panels :$currentTab :$orientation :$params />
    </div>
@endif
