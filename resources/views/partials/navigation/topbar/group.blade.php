@if($group->label)
    <flux:dropdown>
        <flux:navbar.item icon:trailing="chevron-down" :current="$group->active()">{{ $group->label }}</flux:navbar.item>

        <flux:menu>
            @foreach($group->items as $item)
                @include('partials.navigation.topbar.menu-item', ['item' => $item])
            @endforeach
        </flux:menu>
    </flux:dropdown>
@else
    <flux:separator vertical variant="subtle" class="my-2" />

    @foreach($group->items as $item)
        {{ $item->render($style) }}
    @endforeach
@endif
