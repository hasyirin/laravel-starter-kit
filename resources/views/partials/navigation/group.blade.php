@if($group->label)
    <div class="not-in-data-flux-sidebar-collapsed-desktop:hidden">
        <flux:menu.separator />
    </div>
    <flux:menu.heading class="mt-2 uppercase in-data-flux-sidebar-collapsed-desktop:hidden">{{ $group->label }}</flux:menu.heading>
@else
    <flux:menu.separator />
@endif

@foreach($group->items as $item)
    {{ $item }}
@endforeach
