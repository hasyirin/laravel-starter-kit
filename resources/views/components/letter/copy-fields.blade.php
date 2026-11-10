@props(['prefix', 'rows' => [], 'organizations' => [], 'departments' => [], 'disabled' => false])

<x-field :label="__('letters/letters.copies')" :name="$prefix">
    <div class="space-y-2">
        @foreach($rows as $index => $copy)
            <div class="flex items-start gap-2" wire:key="copy-{{ $index }}">
                <div class="w-40 shrink-0">
                    <x-select
                        wire:model.live="{{ $prefix }}.{{ $index }}.kind"
                        :options="['organization' => __('letters/letters.copy_organization'), 'department' => __('letters/letters.copy_department'), 'text' => __('letters/letters.copy_text')]"
                        :disabled="$disabled"
                    />
                </div>
                <div class="grow">
                    @if(data_get($copy, 'kind') === 'organization')
                        <x-select wire:model="{{ $prefix }}.{{ $index }}.target" :options="$organizations" :disabled="$disabled" />
                    @elseif(data_get($copy, 'kind') === 'department')
                        <x-select wire:model="{{ $prefix }}.{{ $index }}.target" :options="$departments" :disabled="$disabled" />
                    @else
                        <x-input wire:model="{{ $prefix }}.{{ $index }}.text" autocomplete="off" :disabled="$disabled" />
                    @endif
                </div>
                @if(! $disabled)
                    <flux:button
                        icon="trash"
                        variant="ghost"
                        :aria-label="__('common/buttons.delete')"
                        x-on:click="{{ '$wire.$set('.json_encode($prefix).', $wire.$get('.json_encode($prefix).').filter((_, position) => position !== '.json_encode($index).'))' }}"
                    />
                @endif
            </div>
        @endforeach
        @if(! $disabled)
            <flux:button
                icon="plus"
                size="sm"
                x-on:click="
                    $wire.$set('{{ $prefix }}', [
                        ...($wire.$get('{{ $prefix }}') ?? []),
                        { kind: 'text', target: null, text: '' },
                    ])
                "
            >
                {{ __('letters/letters.copy') }}
            </flux:button>
        @endif
    </div>
</x-field>
