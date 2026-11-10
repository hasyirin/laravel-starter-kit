<div>
    {{ $this->table }}

    <livewire:components.letter-modal :$source />

    <flux:modal wire:model="showingScan" class="md:min-w-xl">
        @if($showingScan)
            <form wire:submit="saveScan" class="space-y-6">
                <flux:heading size="lg">{{ __('letters/letters.upload_scan') }}</flux:heading>

                <x-file-upload wire:model="scan" :rules="['mimes:pdf,jpg,jpeg,png', 'max:10240']" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('common/buttons.cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">{{ __('common/buttons.save') }}</flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
