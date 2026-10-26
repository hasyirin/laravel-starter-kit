@props([
    'hint' => null,
    'actions' => null,
    'prefix' => null,
    'suffix' => null,
])

<x-input type="password" viewable :$hint :$actions :$prefix :$suffix {{ $attributes }} />
