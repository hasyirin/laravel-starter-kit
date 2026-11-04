@use(App\Livewire\Components\Option)

<flux:main>
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('user/settings.appearance.title') }}</flux:heading>

    <x-settings.layout :heading="__('user/settings.appearance.heading')">
        <x-radio
            x-data
            variant="segmented"
            x-model="$flux.appearance"
            :options="[
                Option::make('light')->label(__('user/settings.appearance.light'))->icon('sun'),
                Option::make('dark')->label(__('user/settings.appearance.dark'))->icon('moon'),
                Option::make('system')->label(__('user/settings.appearance.system'))->icon('computer-desktop'),
            ]"
        />
    </x-settings.layout>
</flux:main>
