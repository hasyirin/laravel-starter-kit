<div class="relative">
    <flux:button variant="ghost" size="sm" icon="bell" :aria-label="__('navigation.notifications')" />

    @if($unreadNotificationsCount)
        <flux:badge size="sm" color="red" class="pointer-events-none absolute -end-1 -top-1">{{ $unreadNotificationsCount }}</flux:badge>
    @endif
</div>
