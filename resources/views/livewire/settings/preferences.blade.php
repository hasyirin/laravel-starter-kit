<flux:main>
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('user/settings.preferences.title') }}</flux:heading>

    <x-settings.layout :heading="__('user/settings.preferences.heading')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <x-field :label="__('common/inputs.timezone')" name="timezone">
                <flux:select wire:model.live="timezone" data-input-control>
                    @foreach($this->timezones as $region => $timezones)
                        <optgroup label="{{ $region }}" wire:key="region-{{ $region }}">
                            @foreach($timezones as $timezone => $timezoneName)
                                <option value="{{ $timezone }}" wire:key="timezone-{{ $timezone }}">{{ $timezoneName }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </flux:select>
            </x-field>

            <x-field
                :label="__('common/inputs.locale')"
                name="locale"
                :description-trailing="$this->example === null ? null : __('user/settings.preferences.example', ['example' => $this->example])"
            >
                <flux:select wire:model.live="locale" data-input-control>
                    @foreach($this->locales as $locale => $localeName)
                        <flux:select.option :value="$locale" wire:key="locale-{{ $locale }}">{{ $localeName }}</flux:select.option>
                    @endforeach
                </flux:select>
            </x-field>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('common/buttons.save') }}</flux:button>
            </div>
        </form>
    </x-settings.layout>
</flux:main>
