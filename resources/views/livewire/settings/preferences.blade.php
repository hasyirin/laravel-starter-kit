@use(App\Livewire\Components\Option)

<div class="space-y-6">
    <form wire:submit="save">
        <x-card body="divided" body-class="grid gap-6 sm:grid-cols-2">
            <x-slot:heading level="2">
                {{ __('user/settings.preferences.heading') }}
            </x-slot:heading>

            <x-select wire:model.live="timezone" :label="__('common/inputs.timezone')" :options="$this->timezones" />

            @if($this->languages->count() > 1)
                <x-select wire:model="language" :label="__('common/inputs.language')" :options="$this->languages->all()" :searchable="false" />
            @endif

            <x-select
                wire:model.live="locale"
                :label="__('common/inputs.locale')"
                :options="$this->locales"
                :searchable="false"
                :description-trailing="$this->example === null ? null : __('user/settings.preferences.example', ['example' => $this->example])"
            />

            <x-slot:footer>
                <flux:card.actions>
                    <flux:button variant="primary" type="submit">{{ __('common/buttons.save') }}</flux:button>
                </flux:card.actions>
            </x-slot:footer>
        </x-card>
    </form>

    <x-card body="divided">
        <x-slot:heading level="2">
            {{ __('user/settings.appearance.heading') }}
        </x-slot:heading>

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
    </x-card>
</div>
