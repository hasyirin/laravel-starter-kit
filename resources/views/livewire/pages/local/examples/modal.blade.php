<div>
    <flux:button wire:click="show">{{ __('docs.examples.modal.open') }}</flux:button>

    <flux:modal wire:model="open" class="md:min-w-md">
        @if ($open)
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">{{ __('docs.examples.modal.open') }}</flux:heading>

                <x-input wire:model="name" :label="__('docs.examples.modal.name')" :$disabled required />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('common/buttons.cancel') }}</flux:button>
                    </flux:modal.close>

                    @unless ($disabled)
                        <flux:button type="submit" variant="primary">{{ __('common/buttons.save') }}</flux:button>
                    @endunless
                </div>
            </form>
        @endif
    </flux:modal>
</div>
