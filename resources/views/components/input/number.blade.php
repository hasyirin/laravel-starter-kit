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
    'decimals' => 2,
    'comma' => ',',
    'separator' => '.',
    'disableNegatives' => false,
    'default' => null,
    'defaultLabel' => null,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');

    $modelAttributes = $attributes->whereStartsWith('wire:model');
    $model = $modelAttributes->first();
    $name = $model ?? $attributes->get('name');

    $decimals = (int) $decimals;
    $hasDefault = $default !== null;
    $hasAddons = filled($prefix) || filled($suffix);

    $addonClass = match ($size) {
        'sm' => 'px-3!',
        'xs' => 'px-2.5!',
        default => null,
    };

    // Flux echoes the mask with {{ }}, so an HtmlString keeps its quotes from being escaped twice.
    $mask = new \Illuminate\Support\HtmlString(
        '$money('.($disableNegatives ? "\$input.replace(/-/g, '')" : '$input').', '.\Illuminate\Support\Js::from($separator).', '.\Illuminate\Support\Js::from($comma).', '.$decimals.')',
    );

    $inputmode = match (true) {
        ! $disableNegatives => null,
        $decimals > 0 => 'decimal',
        default => 'numeric',
    };

    $controlAttributes = $attributes->except(['class', 'name', 'value'])->whereDoesntStartWith('wire:model');
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
    x-data="numberField"
    :data-number="json_encode(['decimals' => $decimals, 'comma' => $comma, 'separator' => $separator, 'disableNegatives' => (bool) $disableNegatives])"
    :data-input-default="$hasDefault ? (string) $default : null"
    :data-input-model="$model"
    {{ $attributes->only('class') }}
>
    @if($hasAddons)
        <flux:input.group>
            @if(is_string($prefix))
                <flux:input.group.prefix :$size :class="$addonClass">{{ $prefix }}</flux:input.group.prefix>
            @elseif(filled($prefix))
                {{ $prefix }}
            @endif

            <flux:input
                :$size
                :$required
                :invalid="filled($name) && $errors->has($name)"
                :$inputmode
                :mask:dynamic="$mask"
                :class="$inputClass"
                data-input-control
                :attributes="$controlAttributes"
            />

            @if(is_string($suffix))
                <flux:input.group.suffix :$size :class="$addonClass">{{ $suffix }}</flux:input.group.suffix>
            @elseif(filled($suffix))
                {{ $suffix }}
            @endif
        </flux:input.group>
    @else
        <flux:input
            :$size
            :$required
            :invalid="filled($name) && $errors->has($name)"
            :$inputmode
            :mask:dynamic="$mask"
            :class="$inputClass"
            data-input-control
            :attributes="$controlAttributes"
        />
    @endif

    <input type="hidden" data-input-number-model {{ $attributes->only(['name', 'value']) }} {{ $modelAttributes }} />

    @if($hasDefault)
        <flux:field variant="inline">
            <flux:checkbox
                :disabled="(bool) $attributes->get('disabled') || (bool) $attributes->get('readonly')"
                x-bind:checked="matchesDefault"
                x-on:change="useDefault($event.target.checked)"
            />
            <flux:label class="font-normal">
                {{ $defaultLabel ?? __('common/inputs.use_default', ['value' => number_format((float) $default, $decimals, $separator, $comma)]) }}
            </flux:label>
        </flux:field>
    @endif
</x-field>
