@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'invalid' => null,
    'time' => 'input',
    'withToday' => true,
    'selectableHeader' => true,
    'locale' => null,
    'min' => null,
    'max' => null,
    'openTo' => null,
    'unavailable' => null,
    'trigger' => null,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');

    $modelAttributes = $attributes->whereStartsWith('wire:model');
    $model = $modelAttributes->first();
    $name = $model ?? $attributes->get('name');
    $preferences = \App\Domain\User\Support\Preferences::current();

    // The picker shows the user's time zone, so limits given as instants move there too.
    // They are props because Blade turns an object in an attribute into a string without its time zone.
    $local = fn (mixed $value): mixed => match (true) {
        $value instanceof \DateTimeInterface => $preferences->local($value)->format('Y-m-d\TH:i'),
        $value === 'now' => $preferences->local(now())->format('Y-m-d\TH:i'),
        $value === 'today' => $preferences->local(now())->format('Y-m-d'),
        default => $value,
    };

    $controlAttributes = $attributes->except('class')->whereDoesntStartWith('wire:model');
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
    :x-data="$model === null ? null : 'datetimeField'"
    :data-timezone="$preferences->timezone"
    :data-app-timezone="config('app.timezone')"
    :data-input-model="$model"
    {{ $attributes->only('class') }}
>
    <flux:datetime-picker
        :$time
        :with-today="$withToday"
        :selectable-header="$selectableHeader"
        :locale="$locale ?? $preferences->locale"
        :min="$local($min)"
        :max="$local($max)"
        :open-to="$local($openTo)"
        :unavailable="$unavailable === null ? null : collect($unavailable)->map($local)->all()"
        :$trigger
        :$required
        :invalid="$invalid ?? (filled($name) && ($errors->has($name) || $errors->has($name.'.*')))"
        :class="$inputClass"
        data-input-control
        :attributes="$controlAttributes"
    />

    @if($model !== null)
        <div hidden data-input-datetime-model {{ $modelAttributes }}></div>
    @endif
</x-field>
