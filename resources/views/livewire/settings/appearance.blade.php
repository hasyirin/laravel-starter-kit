<flux:main>
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('user/settings.appearance.title') }}</flux:heading>

    <x-settings.layout :heading="__('user/settings.appearance.heading')" :subheading=" __('user/settings.appearance.description')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('user/settings.appearance.light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('user/settings.appearance.dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('user/settings.appearance.system') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</flux:main>
