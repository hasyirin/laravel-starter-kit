@props([
    'tabs',
    'currentTab' => null,
    'queryAs' => 'tab',
    'ariaLabel' => null,
])

@php
    $defaultSlug = collect($tabs)->first(fn (App\Livewire\Components\Tab $tab): bool => $tab->isDefault())?->slug() ?? '';
@endphp

@if($tabs !== [])
    <flux:navbar :attributes="$attributes->merge(['aria-label' => $ariaLabel, 'x-data' => 'tabs('.Js::from($queryAs).', '.Js::from($defaultSlug).')'])">
        @include('partials.tabs.nav-items', ['tabs' => $tabs, 'currentTab' => $currentTab, 'item' => 'flux::navbar.item'])
    </flux:navbar>
@endif
