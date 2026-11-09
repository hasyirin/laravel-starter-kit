@php($disabled ??= false)

<x-card.group form wire:submit="save" :$disabled>
    <x-card accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
        <x-slot:heading level="2">
            {{ __('permission/roles.details') }}
        </x-slot:heading>

        <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required :disabled="! $this->editable('name')" />
    </x-card>

    <x-card accordion body="divided">
        <x-slot:heading level="2">
            {{ __('user/users.permissions.heading') }}
        </x-slot:heading>

        @include('livewire.pages.internal.controls.partials.permission-tree')
    </x-card>

    <x-card body="divided">
        <x-slot:footer>
            <flux:card.actions>
                <flux:button :href="route('internal.controls.roles.index')" variant="subtle" wire:navigate>{{ __('common/buttons.back') }}</flux:button>

                @unless($disabled)
                    <flux:button variant="primary" type="submit">{{ $submit }}</flux:button>
                @endunless
            </flux:card.actions>
        </x-slot:footer>
    </x-card>
</x-card.group>
