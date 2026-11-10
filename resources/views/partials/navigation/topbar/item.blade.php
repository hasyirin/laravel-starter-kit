@if(filled($item->children))
    <flux:dropdown>
        <flux:navbar.item :icon="$item->icon" icon:trailing="chevron-down" :current="$item->active()">{{ $item->label }}</flux:navbar.item>

        <flux:menu>
            @foreach($item->children as $child)
                @include('partials.navigation.topbar.menu-item', ['item' => $child])
            @endforeach
        </flux:menu>
    </flux:dropdown>
@else
    <flux:navbar.item :href="$item->href()" :current="$item->active()" :icon="$item->icon" :badge="$item->badge" :wire:navigate="$item->hasRoute()">
        {{ $item->label }}
    </flux:navbar.item>
@endif
