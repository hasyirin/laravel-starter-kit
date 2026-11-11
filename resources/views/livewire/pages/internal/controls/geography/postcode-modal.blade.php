<div>
    <flux:modal wire:model="showing" class="md:min-w-md">
        @if($showing)
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">
                    {{ __($this->record->exists ? 'common/records.edit' : 'common/records.add', ['record' => $this::label()]) }}
                </flux:heading>

                @unless($postOffice)
                    <div class="grid gap-6 sm:grid-cols-2">
                        <x-entry :label="__('geography/states.state')">{{ $form->postOffice->state->name }}</x-entry>

                        <x-entry :label="__('geography/cities.city')">{{ $form->postOffice->name }}</x-entry>
                    </div>
                @endunless

                <x-input wire:model="form.code" :label="__('common/inputs.code')" autocomplete="off" required :$disabled />

                <x-map latitude-model="form.latitude" longitude-model="form.longitude" :$disabled />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('common/buttons.cancel') }}</flux:button>
                    </flux:modal.close>

                    @unless($disabled)
                        <flux:button type="submit" variant="primary">{{ __('common/buttons.save') }}</flux:button>
                    @endunless
                </div>
            </form>
        @endif
    </flux:modal>
</div>
