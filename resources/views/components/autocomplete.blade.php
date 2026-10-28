@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'options' => [],
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $name = $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('name');
    $options = \App\Livewire\Components\Option::collect($options);

    // Flux links the label to the container of the input, so the input itself needs a name.
    $ariaLabel = $attributes->get('aria-label') ?? (filled($label) ? trim(html_entity_decode(strip_tags((string) $label), ENT_QUOTES | ENT_HTML5)) : null);
@endphp

{{-- A press on an option must not move the focus out of the input, or .blur sends the text before the pick. --}}
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
    x-data
    x-on:mousedown="$event.target.closest('ui-options') && $event.preventDefault()"
    {{ $attributes->only('class') }}
>
    <flux:autocomplete :$required :aria-label="$ariaLabel" :class="$inputClass" data-input-control {{ $attributes->except(['class', 'aria-label']) }}>
        @foreach($options as $option)
            <flux:autocomplete.item :disabled="$option->isDisabled()">{{ $option->resolvedText() }}</flux:autocomplete.item>
        @endforeach

        {{ $slot }}
    </flux:autocomplete>
</x-field>
