<flux:main container>
    <div class="space-y-8">
        <x-breadcrumbs :items="[['label' => __('navigation.manual_letter')]]" />

        <flux:heading size="xl">{{ __('navigation.manual_letter') }}</flux:heading>

        <form wire:submit="print" class="max-w-2xl space-y-6">
            <x-select
                wire:model.live="form.type"
                :label="__('letters/letters.type')"
                :options="$this->types"
                :placeholder="__('letters/letters.choose_type')"
                required
            />

            @if($this->letterType)
                <x-radio
                    wire:model.live="start"
                    variant="segmented"
                    :label="__('letters/manual.start')"
                    :options="['blank' => __('letters/manual.start_blank'), 'source' => __('letters/manual.start_source'), 'letter' => __('letters/manual.start_letter')]"
                />

                @if($start !== 'blank')
                    <x-select
                        wire:model.live="sourceId"
                        :label="__('letters/manual.source')"
                        :options="$this->sources"
                        :placeholder="__('letters/manual.choose_source')"
                        search-model="sourceSearch"
                        :search-placeholder="__('letters/manual.search_source')"
                    />
                @endif

                @if($start === 'letter' && $sourceId !== null)
                    <x-select
                        wire:model.live="letterId"
                        :label="__('letters/manual.letter')"
                        :options="$this->letters"
                        :placeholder="__('letters/manual.choose_letter')"
                    />
                @endif

                <x-input wire:model="form.reference_number" :label="__('letters/letters.reference_number')" autocomplete="off" />

                <x-input.date wire:model="form.date" :label="__('letters/letters.date')" />

                @if($this->letterType->signer() !== \App\Domain\Shared\Letter\Enums\SignerRequirement::Hidden)
                    <x-select
                        wire:model="form.signer_id"
                        :label="__('letters/letters.signer')"
                        :options="$this->signers"
                        :placeholder="__('letters/letters.choose_signer')"
                        :required="$this->letterType->signer() === \App\Domain\Shared\Letter\Enums\SignerRequirement::Required"
                        clearable
                    />
                @endif

                <x-letter.copy-fields
                    prefix="form.carbon_copies"
                    :rows="$form->carbon_copies"
                    :organizations="$this->organizations"
                    :departments="$this->departments"
                />

                @if($this->letterType->offersLetterhead())
                    <x-switch wire:model="form.with_letterhead" :label="__('letters/letterheads.letterhead')" :disabled="$form->computer_signed" />
                @endif

                @if($this->letterType->offersComputerSigned())
                    <x-switch wire:model.live="form.computer_signed" :label="__('letters/letters.computer_signed_label')" />
                @endif

                @if($this->letterType->fields() !== [])
                    <x-letter.data-fields :fields="$this->letterType->fields()" prefix="form.data" :values="$form->data" />
                @endif

                @if($this->letterType->extraFields() !== [])
                    <x-letter.data-fields :fields="$this->letterType->extraFields()" prefix="form.extra" :values="$form->extra" />
                @endif

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="preview">{{ __('letters/letters.preview') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('letters/letters.print') }}</flux:button>
                </div>
            @endif
        </form>
    </div>
</flux:main>
