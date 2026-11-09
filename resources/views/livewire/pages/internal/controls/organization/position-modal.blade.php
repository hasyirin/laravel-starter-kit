<div>
    <flux:modal wire:model="showing" class="md:min-w-md">
        @if($showing)
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">
                    {{ __($this->record->exists ? 'common/records.edit' : 'common/records.add', ['record' => $this::label()]) }}
                </flux:heading>

                @unless($department)
                    <x-select
                        wire:model="form.department_id"
                        :label="__('organization/departments.department')"
                        :options="$this->departments"
                        :placeholder="__('organization/positions.no_department')"
                        clearable
                        :disabled="! $this->editable('department')"
                    />
                @endunless

                <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required :$disabled />

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
