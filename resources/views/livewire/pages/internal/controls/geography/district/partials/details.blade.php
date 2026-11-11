@php($disabled ??= false)

<x-card.group form wire:submit="save" :$disabled>
    <x-card accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('geography/districts.details') }}
        </x-slot:heading>

        <x-input
            wire:model="form.code"
            :label="__('common/inputs.code')"
            autocomplete="off"
            required
            :disabled="$disabled || ! $this->form->editable('code')"
            :description-trailing="$this->form->editable('code') ? null : __('geography/geography.locality_code')"
        />

        <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required />
    </x-card>

    <x-card body="divided">
        <x-slot:footer>
            <flux:card.actions>
                <flux:button
                    :href="route('internal.controls.geography.countries.states.show', ['country' => $country, 'state' => $state, 'tab' => 'districts'])"
                    variant="subtle"
                    wire:navigate
                >
                    {{ __('common/buttons.back') }}
                </flux:button>

                @unless($disabled)
                    <flux:button variant="primary" type="submit">{{ $submit }}</flux:button>
                @endunless
            </flux:card.actions>
        </x-slot:footer>
    </x-card>
</x-card.group>
