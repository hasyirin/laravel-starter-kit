@if(filled($item->children))
    <flux:sidebar.group expandable :expanded="$item->active()" :icon="$item->icon" :heading="$item->label">
        @foreach($item->children as $child)
            {{ $child }}
        @endforeach
    </flux:sidebar.group>
@else
    <flux:sidebar.item :href="$item->href()" :current="$item->active()" :icon="$item->icon" :badge="$item->badge" :wire:navigate="$item->hasRoute()">
        {{ $item->label }}
    </flux:sidebar.item>
@endif
