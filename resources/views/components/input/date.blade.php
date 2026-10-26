@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'type' => null,
    'withToday' => true,
    'selectableHeader' => true,
    'locale' => null,
    'trigger' => null,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $name = $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('name');

    // The typed input holds one date, so a range opens from the button.
    $type ??= $attributes->get('mode') === 'range' ? 'button' : 'input';
@endphp

<x-field :$label :$description :$descriptionTrailing :$hint :$actions :$badge :$required :$name :$noErrorMessage {{ $attributes->only('class') }}>
    <flux:date-picker
        :$type
        :with-today="$withToday"
        :selectable-header="$selectableHeader"
        :locale="$locale ?? config('system.locale')"
        :$trigger
        :$required
        :class="$inputClass"
        data-input-control
        {{ $attributes->except('class') }}
    />
</x-field>
