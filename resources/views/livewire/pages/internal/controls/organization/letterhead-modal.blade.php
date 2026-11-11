<div>
    <flux:modal wire:model="showing" class="md:min-w-xl">
        @if($showing)
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">
                    {{ __($this->record->exists ? 'common/records.edit' : 'common/records.add', ['record' => $this::label()]) }}
                </flux:heading>

                <x-input wire:model="form.name" :label="__('common/inputs.name')" autocomplete="off" required :$disabled />

                <x-select
                    wire:model="form.branch_id"
                    :label="__('organization/branches.branch')"
                    :options="$this->branches"
                    :placeholder="__('letters/letterheads.all_branches')"
                    clearable
                    :$disabled
                />

                <x-select
                    wire:model="form.department_id"
                    :label="__('organization/departments.department')"
                    :options="$this->departments"
                    :placeholder="__('letters/letterheads.all_departments')"
                    clearable
                    :$disabled
                />

                @error('placement')
                    <flux:error :message="$message" />
                @enderror

                <x-file-upload
                    wire:model="form.header"
                    :label="__('letters/letterheads.header')"
                    :rules="\App\Livewire\Forms\Internal\LetterheadForm::IMAGE_RULES"
                    :hint="__('letters/letterheads.image_rules')"
                    required
                    :$disabled
                />

                <x-file-upload
                    wire:model="form.footer"
                    :label="__('letters/letterheads.footer')"
                    :rules="\App\Livewire\Forms\Internal\LetterheadForm::IMAGE_RULES"
                    :$disabled
                />

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
