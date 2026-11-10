@if(filled($item->children))
    <flux:menu.submenu :heading="$item->label" :icon="$item->icon">
        @foreach($item->children as $child)
            @include('partials.navigation.topbar.menu-item', ['item' => $child])
        @endforeach
    </flux:menu.submenu>
@else
    <flux:menu.item :href="$item->href()" :icon="$item->icon" :suffix="$item->badge" :wire:navigate="$item->hasRoute()">{{ $item->label }}</flux:menu.item>
@endif
