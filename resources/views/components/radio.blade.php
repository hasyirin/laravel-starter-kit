@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'options' => [],
    'variant' => 'default',
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $name = $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('name');

    // Flux puts a prop that a variant does not use on the radio as an attribute, which breaks on HTML.
    // So only the variants that show icons get the icon, and the default variant, which also puts the label there, gets the label as text.
    $showsIcons = in_array($variant, ['segmented', 'buttons', 'cards'], true);
@endphp

<x-field :$label :$description :$descriptionTrailing :$hint :$actions :$badge :$required :$name :$noErrorMessage {{ $attributes->only('class') }}>
    <flux:radio.group :$variant :$required :class="$inputClass" data-input-control {{ $attributes->except('class') }}>
        @foreach(\App\Livewire\Components\Option::collect($options) as $option)
            <flux:radio
                :value="$option->value()"
                :label="$variant === 'default' ? $option->resolvedText() : $option->resolvedLabel()"
                :description="$option->resolvedDescription()"
                :icon="$showsIcons ? $option->resolvedIcon() : null"
                :disabled="$option->isDisabled()"
                wire:key="option-{{ $option->value() }}"
            />
        @endforeach

        {{ $slot }}
    </flux:radio.group>
</x-field>
