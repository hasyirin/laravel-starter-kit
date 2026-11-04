<flux:main>
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('user/settings.preferences.title') }}</flux:heading>

    <x-settings.layout :heading="__('user/settings.preferences.heading')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <x-select wire:model.live="timezone" :label="__('common/inputs.timezone')" :options="$this->timezones" />

            <x-select
                wire:model.live="locale"
                :label="__('common/inputs.locale')"
                :options="$this->locales"
                :searchable="false"
                :description-trailing="$this->example === null ? null : __('user/settings.preferences.example', ['example' => $this->example])"
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('common/buttons.save') }}</flux:button>
            </div>
        </form>
    </x-settings.layout>
</flux:main>
