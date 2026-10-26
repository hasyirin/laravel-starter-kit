@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
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
@endphp

<x-field :$label :$description :$descriptionTrailing :$hint :$actions :$badge :$required :$name :$noErrorMessage {{ $attributes->only('class') }}>
    <flux:datetime-picker
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
