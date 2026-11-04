@aware(['disabled' => false])

@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'prefix' => null,
    'suffix' => null,
])

@php
    $attributes = $attributes->merge(['disabled' => $disabled]);
@endphp

<x-input type="password" viewable :$label :$description :$descriptionTrailing :$hint :$actions :$badge :$prefix :$suffix {{ $attributes }} />
