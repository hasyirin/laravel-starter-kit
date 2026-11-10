@aware(['disabled' => false])

@props([
    'label' => null,
    'required' => false,
    'latitudeModel' => null,
    'longitudeModel' => null,
    'latitude' => null,
    'longitude' => null,
    'clickable' => true,
    'editableInputs' => true,
])

@php
    $disabled = (bool) $attributes->get('disabled', $disabled);
    $bound = filled($latitudeModel) && filled($longitudeModel);
    $readonly = ! $bound || ! $editableInputs;
    $layers = collect(config()->array('map.layers'));

    throw_if($layers->isEmpty(), LogicException::class, 'Set at least one layer in map.layers in config/map.php.');

    $options = [
        'center' => [config()->float('map.center.latitude'), config()->float('map.center.longitude')],
        'zoom' => config()->integer('map.zoom'),
        'pointZoom' => config()->integer('map.point_zoom'),
        'layers' => $layers
            ->map(fn (array $layer): array => [
                'label' => __($layer['label']),
                'url' => $layer['url'],
                'attribution' => $layer['attribution'] ?? null,
                'maxZoom' => $layer['max_zoom'] ?? 19,
                'subdomains' => $layer['subdomains'] ?? null,
            ])
            ->values()
            ->all(),
        'latitudeModel' => $bound ? $latitudeModel : null,
        'longitudeModel' => $bound ? $longitudeModel : null,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'clickable' => $bound && $clickable && ! $disabled,
    ];

    $inputAttributes = fn (?string $model, mixed $value) => new \Illuminate\View\ComponentAttributeBag($bound ? ['wire:model' => $model] : ['value' => $value]);
@endphp

<div {{ $attributes->except('disabled')->class('space-y-6') }} x-data="map" data-map="{{ json_encode($options) }}">
    <x-field :label="$label ?? __('common/inputs.location')" no-error-message>
        <div
            class="isolate z-0 h-80 overflow-hidden rounded-lg border border-zinc-200 dark:border-white/10"
            role="application"
            aria-label="{{ $label ?? __('common/inputs.location') }}"
            x-ref="canvas"
            wire:ignore
            data-input-control
            data-map-canvas
        ></div>
    </x-field>

    <div class="grid gap-6 sm:grid-cols-2">
        <x-input.number
            :label="__('common/inputs.latitude')"
            :$required
            decimals="6"
            comma=""
            :$readonly
            :$disabled
            :attributes="$inputAttributes($latitudeModel, $latitude)"
        />

        <x-input.number
            :label="__('common/inputs.longitude')"
            :$required
            decimals="6"
            comma=""
            :$readonly
            :$disabled
            :attributes="$inputAttributes($longitudeModel, $longitude)"
        />
    </div>
</div>
