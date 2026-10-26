@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'prefix' => null,
    'suffix' => null,
    'size' => null,
    'required' => false,
    'case' => null,
    'default' => null,
    'defaultLabel' => null,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');

    $model = $attributes->whereStartsWith('wire:model')->first();
    $name = $model ?? $attributes->get('name');

    if ($case !== null && ! in_array($case, ['upper', 'lower', 'title', 'sentence'], true)) {
        throw new \InvalidArgumentException("Invalid case [{$case}]. Use upper, lower, title, or sentence.");
    }

    $hasDefault = $default !== null;
    $hasAddons = filled($prefix) || filled($suffix);

    $addonClass = match ($size) {
        'sm' => 'px-3!',
        'xs' => 'px-2.5!',
        default => null,
    };
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
    :x-data="$case !== null || $hasDefault ? 'inputField' : null"
    :data-input-case="$case"
    :data-input-default="$hasDefault ? (string) $default : null"
    :data-input-model="$hasDefault ? $model : null"
    {{ $attributes->only('class') }}
>
    @if($hasAddons)
        <flux:input.group>
            @if(is_string($prefix))
                <flux:input.group.prefix :$size :class="$addonClass">{{ $prefix }}</flux:input.group.prefix>
            @elseif(filled($prefix))
                {{ $prefix }}
            @endif

            <flux:input :$size :$required :class="$inputClass" data-input-control {{ $attributes->except('class') }} />

            @if(is_string($suffix))
                <flux:input.group.suffix :$size :class="$addonClass">{{ $suffix }}</flux:input.group.suffix>
            @elseif(filled($suffix))
                {{ $suffix }}
            @endif
        </flux:input.group>
    @else
        <flux:input :$size :$required :class="$inputClass" data-input-control {{ $attributes->except('class') }} />
    @endif

    @if($hasDefault)
        <flux:field variant="inline">
            <flux:checkbox
                :disabled="(bool) $attributes->get('disabled') || (bool) $attributes->get('readonly')"
                x-bind:checked="matchesDefault"
                x-on:change="useDefault($event.target.checked)"
            />
            <flux:label class="font-normal">{{ $defaultLabel ?? __('common/inputs.use_default', ['value' => $default]) }}</flux:label>
        </flux:field>
    @endif
</x-field>
