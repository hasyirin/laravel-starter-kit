@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'rules' => [],
    'multiple' => false,
    'max' => null,
    'accept' => null,
    'heading' => null,
    'text' => null,
    'inline' => false,
    'disabled' => false,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $directive = array_key_first($attributes->whereStartsWith('wire:model')->getAttributes());

    if ($directive === null) {
        throw new \InvalidArgumentException('x-file-upload needs wire:model.');
    }

    $name = $attributes->get($directive);

    $params = [
        $directive => $name,
        'fileRules' => is_string($rules) ? explode('|', $rules) : $rules,
        'multiple' => $multiple,
        'max' => $max,
        'accept' => $accept,
        'heading' => $heading,
        'text' => $text,
        'inline' => $inline,
        'disabled' => $disabled,
    ];
@endphp

<x-field :$label :$description :$descriptionTrailing :$hint :$actions :$badge :$required :$name :$noErrorMessage {{ $attributes->only('class') }}>
    @livewire('components.file-upload', $params, key($name))
</x-field>
