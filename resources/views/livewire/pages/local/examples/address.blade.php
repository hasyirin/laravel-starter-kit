<div>
    <x-card :$disabled>
        <x-address :form="$address" :label="$label" />

        @unless($disabled)
            <x-slot:footer>
                <flux:card.actions>
                    <flux:button variant="primary" wire:click="save">{{ __('common/buttons.save') }}</flux:button>
                </flux:card.actions>
            </x-slot:footer>
        @endunless
    </x-card>
</div>
