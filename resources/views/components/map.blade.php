@aware(['disabled' => false])

@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'caption' => null,
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
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $bound = filled($latitudeModel) && filled($longitudeModel);
    $showingInputs = $bound && $editableInputs;
    $livewire = $__livewire ?? null;
    $currentLatitude = $bound ? data_get($livewire, $latitudeModel) : $latitude;
    $currentLongitude = $bound ? data_get($livewire, $longitudeModel) : $longitude;
    $location = null;

    if (is_numeric($currentLatitude) && is_numeric($currentLongitude)) {
        $location = collect([$currentLatitude, $currentLongitude])
            ->map(fn (int|float|string $value): string => str(number_format((float) $value, 6, '.', ''))
                ->rtrim('0')
                ->rtrim('.')
                ->value())
            ->implode(', ');
    }

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
@endphp

<div {{ $attributes->except('disabled')->class('space-y-6') }} x-data="map" data-map="{{ json_encode($options) }}">
    <x-field :label="$label ?? __('common/inputs.location')" :$description :$descriptionTrailing :$required no-error-message>
        <div
            class="isolate z-0 h-80 overflow-hidden rounded-lg border border-zinc-200 dark:border-white/10"
            role="application"
            aria-label="{{ $label ?? __('common/inputs.location') }}"
            x-ref="canvas"
            wire:ignore
            data-input-control
            data-map-canvas
        ></div>

        @unless($showingInputs)
            <div class="text-sm" data-map-location>
                @if($bound)
                    <span wire:ignore>
                        <span class="text-zinc-800 dark:text-white" x-text="location">{{ $location }}</span>
                        <span class="text-zinc-500 dark:text-white/60" x-show="location === null" @style(['display: none' => $location !== null])>
                            {{ __('common/inputs.no_location') }}
                        </span>
                    </span>
                @elseif($location !== null)
                    <span class="text-zinc-800 dark:text-white">{{ $location }}</span>
                @else
                    <span class="text-zinc-500 dark:text-white/60">{{ __('common/inputs.no_location') }}</span>
                @endif

                @if(filled($caption))
                    <span class="text-zinc-500 dark:text-white/60">· {{ $caption }}</span>
                @endif
            </div>
        @endunless

        @if($bound && ! $editableInputs)
            <flux:error :message="$errors->first($latitudeModel) ?: $errors->first($longitudeModel)" />
        @endif
    </x-field>

    @if($showingInputs)
        <div class="grid gap-6 sm:grid-cols-2">
            <x-input.number :label="__('common/inputs.latitude')" decimals="6" comma="" :$disabled wire:model="{{ $latitudeModel }}" />

            <x-input.number :label="__('common/inputs.longitude')" decimals="6" comma="" :$disabled wire:model="{{ $longitudeModel }}" />
        </div>
    @endif
</div>
