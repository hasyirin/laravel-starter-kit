@aware(['disabled' => false])

@props([
    'align' => 'left',
])

@php
    $attributes = $attributes->merge(['disabled' => $disabled]);
@endphp

<flux:switch :$align {{ $attributes }} />
