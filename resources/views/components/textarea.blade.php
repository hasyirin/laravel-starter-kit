@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'case' => null,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $name = $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('name');

    if ($case !== null && ! in_array($case, ['upper', 'lower', 'title', 'sentence'], true)) {
        throw new \InvalidArgumentException("Invalid case [{$case}]. Use upper, lower, title, or sentence.");
    }
@endphp

<x-field
    :$label
    :$description
    :$descriptionTrailing
    :$hint
    :$actions
    :$badge
    :$required
    :$name
    :$noErrorMessage
    :x-data="$case === null ? null : 'inputField'"
    :data-input-case="$case"
    {{ $attributes->only('class') }}
>
    <flux:textarea :$required :class="$inputClass" data-input-control {{ $attributes->except('class') }}>{{ $slot }}</flux:textarea>
</x-field>
