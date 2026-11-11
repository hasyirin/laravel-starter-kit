@aware(['disabled' => false])

@props([
    'fields' => [],
    'prefix',
    'values' => [],
    'locked' => [],
    'disabled' => false,
])

<div {{ $attributes->class('space-y-6') }}>
    @foreach($fields as $field)
        @php
            $key = $prefix.'.'.$field->name;
            $label = $field->label ?? str($field->name)->headline()->value();
            $fieldDisabled = $disabled || in_array($field->name, $locked, true);
            $required = ! $field->isOptional;
            $current = data_get($values, $field->name);
            $type = $field->type->value;
        @endphp

        @if($type === 'text')
            <x-input :$label :$required :disabled="$fieldDisabled" wire:model="{{ $key }}" />
        @elseif($type === 'lines')
            <x-textarea :$label :$required :disabled="$fieldDisabled" wire:model="{{ $key }}" />
        @elseif($type === 'date')
            <x-input.date :$label :$required :disabled="$fieldDisabled" wire:model="{{ $key }}" />
        @elseif($type === 'number')
            <x-input.number :$label :$required :disabled="$fieldDisabled" wire:model="{{ $key }}" />
        @elseif($type === 'yes-no')
            <x-switch :$label :disabled="$fieldDisabled" wire:model="{{ $key }}" />
        @elseif($type === 'choice')
            <x-select :$label :$required :options="$field->options" :disabled="$fieldDisabled" wire:model="{{ $key }}" />
        @elseif($type === 'image')
            <x-file-upload
                :$label
                :$required
                :rules="['mimetypes:'.implode(',', \App\Domain\Shared\Letter\Support\Field::RASTER_TYPES), 'max:'.\App\Domain\Shared\Letter\Support\Field::MAX_IMAGE_KILOBYTES]"
                :disabled="$fieldDisabled"
                wire:model="{{ $key }}"
            />
        @elseif($type === 'group')
            @php
                $groupOn = (bool) data_get($values, '_groups.'.$field->name);
            @endphp
            <div class="space-y-6">
                <x-switch :$label :disabled="$fieldDisabled" wire:model.live="{{ $prefix }}._groups.{{ $field->name }}" />
                @if($groupOn)
                    <x-letter.data-fields :fields="$field->children" :prefix="$key" :values="$current ?? []" :disabled="$fieldDisabled" />
                @endif
            </div>
        @elseif($type === 'list')
            @php
                $rows = is_array($current) ? array_keys($current) : [];
            @endphp
            <x-field :$label :$required :name="$key">
                <div class="space-y-2" x-data="letterRows(@js($key))">
                    @foreach($rows as $index)
                        <div class="flex items-start gap-2" wire:key="{{ $key }}.{{ $index }}">
                            <div class="grow">
                                <x-input :disabled="$fieldDisabled" wire:model="{{ $key }}.{{ $index }}" />
                            </div>
                            @unless($fieldDisabled)
                                <flux:button
                                    icon="trash"
                                    variant="ghost"
                                    :aria-label="__('common/buttons.delete')"
                                    data-list-remove
                                    x-on:click="removeRow({{ Js::from($index) }})"
                                />
                            @endunless
                        </div>
                    @endforeach
                    @unless($fieldDisabled)
                        <flux:button icon="plus" size="sm" data-list-add x-on:click="addRow(null)">{{ __('letters/letters.row') }}</flux:button>
                    @endunless
                </div>
            </x-field>
        @elseif($type === 'table')
            @php
                $rows = is_array($current) ? array_keys($current) : [];
                $blankRow = collect($field->children)->mapWithKeys(fn ($column): array => [$column->name => null])->all();
            @endphp
            <x-field :$label :$required :name="$key">
                <div class="space-y-4" x-data="letterRows(@js($key))">
                    @foreach($rows as $index)
                        <div class="flex items-start gap-2 rounded-lg border border-zinc-200 p-4 dark:border-white/10" wire:key="{{ $key }}.{{ $index }}">
                            <x-letter.data-fields
                                class="grow"
                                :fields="$field->children"
                                :prefix="$key.'.'.$index"
                                :values="is_array($current[$index] ?? null) ? $current[$index] : []"
                                :disabled="$fieldDisabled"
                            />
                            @unless($fieldDisabled)
                                <flux:button
                                    icon="trash"
                                    variant="ghost"
                                    :aria-label="__('common/buttons.delete')"
                                    data-row-remove
                                    x-on:click="removeRow({{ Js::from($index) }})"
                                />
                            @endunless
                        </div>
                    @endforeach
                    @unless($fieldDisabled)
                        <flux:button icon="plus" size="sm" data-row-add x-on:click="addRow({{ Js::from((object) $blankRow) }})">
                            {{ __('letters/letters.row') }}
                        </flux:button>
                    @endunless
                </div>
            </x-field>
        @endif
    @endforeach
</div>
