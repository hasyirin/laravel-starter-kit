<div>
    <flux:modal wire:model="showing" class="md:min-w-2xl">
        @if($showing)
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">
                    {{ __($this->record->exists ? 'common/records.edit' : 'common/records.add', ['record' => $this::label()]) }}
                </flux:heading>

                <x-select
                    wire:model.live="form.type"
                    :label="__('letters/letters.type')"
                    :options="$this->types"
                    :placeholder="__('letters/letters.choose_type')"
                    required
                    :disabled="! $this->editable('type')"
                />

                @if($this->letterType)
                    @if($this->letterType->numbering() === \App\Domain\Shared\Letter\Enums\NumberingMode::Manual)
                        <x-input
                            wire:model="form.reference_number"
                            :label="__('letters/letters.reference_number')"
                            autocomplete="off"
                            :disabled="! $this->editable('reference_number')"
                        />
                    @endif

                    @if($this->letterType->numbering() === \App\Domain\Shared\Letter\Enums\NumberingMode::AutomaticWithOverride)
                        <x-input.number
                            wire:model="form.sequence_number"
                            :label="__('letters/letters.sequence_number')"
                            :disabled="! $this->editable('sequence_number')"
                        />
                    @endif

                    <x-input.date wire:model="form.date" :label="__('letters/letters.date')" :disabled="! $this->editable('date')" />

                    @if($this->letterType->signer() !== \App\Domain\Shared\Letter\Enums\SignerRequirement::Hidden)
                        <x-select
                            wire:model.live="form.signer_id"
                            :label="__('letters/letters.signer')"
                            :options="$this->signers"
                            :placeholder="__('letters/letters.choose_signer')"
                            :required="$this->letterType->signer() === \App\Domain\Shared\Letter\Enums\SignerRequirement::Required"
                            clearable
                            :disabled="! $this->editable('signer')"
                        />
                    @endif

                    <x-field :label="__('letters/letters.copies')" name="form.carbon_copies">
                        <div class="space-y-2">
                            @foreach($form->carbon_copies as $index => $copy)
                                <div class="flex items-start gap-2" wire:key="copy-{{ $index }}">
                                    <div class="w-40 shrink-0">
                                        <x-select
                                            wire:model.live="form.carbon_copies.{{ $index }}.kind"
                                            :options="['organization' => __('letters/letters.copy_organization'), 'department' => __('letters/letters.copy_department'), 'text' => __('letters/letters.copy_text')]"
                                            :disabled="! $this->editable('carbon_copies')"
                                        />
                                    </div>
                                    <div class="grow">
                                        @if(data_get($copy, 'kind') === 'organization')
                                            <x-select
                                                wire:model="form.carbon_copies.{{ $index }}.target"
                                                :options="$this->organizations"
                                                :disabled="! $this->editable('carbon_copies')"
                                            />
                                        @elseif(data_get($copy, 'kind') === 'department')
                                            <x-select
                                                wire:model="form.carbon_copies.{{ $index }}.target"
                                                :options="$this->departments"
                                                :disabled="! $this->editable('carbon_copies')"
                                            />
                                        @else
                                            <x-input
                                                wire:model="form.carbon_copies.{{ $index }}.text"
                                                autocomplete="off"
                                                :disabled="! $this->editable('carbon_copies')"
                                            />
                                        @endif
                                    </div>
                                    @if($this->editable('carbon_copies'))
                                        <flux:button
                                            icon="trash"
                                            variant="ghost"
                                            :aria-label="__('common/buttons.delete')"
                                            x-on:click="{{ '$wire.$set('.json_encode('form.carbon_copies').', $wire.$get('.json_encode('form.carbon_copies').').filter((_, position) => position !== '.json_encode($index).'))' }}"
                                        />
                                    @endif
                                </div>
                            @endforeach
                            @if($this->editable('carbon_copies'))
                                <flux:button
                                    icon="plus"
                                    size="sm"
                                    x-on:click="
                                        $wire.$set('form.carbon_copies', [
                                            ...($wire.$get('form.carbon_copies') ?? []),
                                            { kind: 'text', target: null, text: '' },
                                        ])
                                    "
                                >
                                    {{ __('letters/letters.copy') }}
                                </flux:button>
                            @endif
                        </div>
                    </x-field>

                    @if($this->letterType->offersLetterhead())
                        <x-switch
                            wire:model="form.with_letterhead"
                            :label="__('letters/letterheads.letterhead')"
                            :description="$this->letterheadName"
                            :disabled="! $this->editable('with_letterhead') || $form->computer_signed"
                        />
                    @endif

                    @if($this->letterType->offersComputerSigned())
                        <x-switch
                            wire:model.live="form.computer_signed"
                            :label="__('letters/letters.computer_signed_label')"
                            :disabled="! $this->editable('computer_signed')"
                        />
                    @endif

                    @if($this->letterType->extraFields() !== [])
                        <x-letter.data-fields
                            :fields="$this->letterType->extraFields()"
                            prefix="form.extra"
                            :values="$form->extra"
                            :locked="$this->letterType->lockedFields()"
                            :disabled="! $this->editable('extra')"
                        />
                    @endif
                @endif

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
