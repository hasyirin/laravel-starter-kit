@props([
    'form' => false,
])

@php
    $tag = $form ? 'form' : 'div';
@endphp

<{{ $tag }} {{ $attributes->class('flex flex-col') }} data-card-group>{{ $slot }}</{{ $tag }}>
