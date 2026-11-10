@use(App\Http\Enums\Area)

@php
    $area = Area::current();
@endphp

<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('navigation.settings') }}">
            <flux:navlist.item :href="route($area->route('user-information.edit'))" wire:navigate>
                {{ __('user/settings.nav.user_information') }}
            </flux:navlist.item>
            <flux:navlist.item :href="route($area->route('preferences.edit'))" wire:navigate>{{ __('user/settings.nav.preferences') }}</flux:navlist.item>
            <flux:navlist.item :href="route($area->route('security.edit'))" wire:navigate>{{ __('user/settings.nav.security') }}</flux:navlist.item>
            <flux:navlist.item :href="route($area->route('appearance.edit'))" wire:navigate>{{ __('user/settings.nav.appearance') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>

        <div class="mt-5 w-full max-w-lg">{{ $slot }}</div>
    </div>
</div>
