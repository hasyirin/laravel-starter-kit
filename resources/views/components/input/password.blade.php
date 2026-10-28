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

<x-input type="password" viewable :$label :$description :$descriptionTrailing :$hint :$actions :$badge :$prefix :$suffix {{ $attributes }} />
