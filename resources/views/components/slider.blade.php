@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'showValue' => true,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $name = $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('name');
    $showsValue = $showValue && $hint === null;
    $valueHint = new \Illuminate\Support\HtmlString('<span class="tabular-nums" x-text="text" data-input-slider-value></span>');
@endphp

<x-field
    :$label
    :$description
    :$descriptionTrailing
    :hint="$showsValue ? $valueHint : $hint"
    :$actions
    :$badge
    :$required
    :$name
    :$noErrorMessage
    :x-data="$showsValue ? 'sliderField' : null"
    {{ $attributes->only('class') }}
>
    <flux:slider :class="$inputClass" data-input-control {{ $attributes->except('class') }}>{{ $slot }}</flux:slider>
</x-field>
