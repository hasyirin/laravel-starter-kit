@php($disabled ??= false)

<x-card.group form wire:submit="save" :$disabled>
    <x-card accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('organization/departments.details') }}
        </x-slot:heading>

        <x-input wire:model="form.code" :label="__('common/inputs.code')" maxlength="10" autocomplete="off" required />

        <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required />
    </x-card>

    <x-card body="divided">
        <x-slot:footer>
            <flux:card.actions>
                <flux:button
                    :href="route('internal.controls.organizations.show', ['organization' => $organization, 'tab' => 'departments'])"
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
