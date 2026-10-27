@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'options' => [],
    'variant' => 'listbox',
    'searchable' => null,
    'searchModel' => null,
    'searchPlaceholder' => null,
    'default' => null,
    'defaultLabel' => null,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');

    $model = $attributes->whereStartsWith('wire:model')->first();
    $name = $model ?? $attributes->get('name');

    if ($searchModel !== null && $variant !== 'listbox') {
        throw new \InvalidArgumentException("search-model needs the listbox variant, not [{$variant}].");
    }

    $options = \App\Livewire\Components\Option::collect($options);
    $searchable = $searchModel !== null || ($searchable ?? $variant === 'listbox');
    $hasDefault = $default !== null;

    if ($hasDefault) {
        $defaultOption = $options->first(fn (\App\Livewire\Components\Option $option): bool => (string) $option->value() === (string) $default);
        $defaultLabel ??= __('common/inputs.use_default', ['value' => $defaultOption?->resolvedText() ?? $default]);
    }

    $controlAttributes = $attributes->except('class');

    if ($searchModel !== null) {
        // The server sends only the matching options, so Flux must not filter them again.
        $controlAttributes['filter'] = false;

        $searchAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
            'wire:model.live.debounce.250ms' => $searchModel,
            'placeholder' => $searchPlaceholder,
        ]));
    }
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
    :x-data="$hasDefault ? 'inputField' : null"
    :data-input-default="$hasDefault ? (string) $default : null"
    :data-input-model="$hasDefault ? $model : null"
    {{ $attributes->only('class') }}
>
    <flux:select :$variant :$searchable :$required :class="$inputClass" data-input-control :attributes="$controlAttributes">
        @if($searchModel !== null)
            <x-slot:search>
                <flux:select.search :attributes="$searchAttributes" />
            </x-slot:search>
        @endif

        @foreach($options as $option)
            <flux:select.option
                :value="$option->value()"
                :selected-label="$option->resolvedSelectedLabel()"
                :description="$option->resolvedDescription()"
                :icon="$variant === 'default' ? null : $option->resolvedIcon()"
                :disabled="$option->isDisabled()"
            >
                {{ $option->resolvedLabel() }}

                @if($variant !== 'default' && filled($option->resolvedBadge()))
                    <flux:badge :color="$option->resolvedColor()" size="sm" class="ms-1.5">{{ $option->resolvedBadge() }}</flux:badge>
                @endif
            </flux:select.option>
        @endforeach

        {{ $slot }}
    </flux:select>

    @if($hasDefault)
        <flux:field variant="inline">
            <flux:checkbox :disabled="(bool) $attributes->get('disabled')" x-bind:checked="matchesDefault" x-on:change="useDefault($event.target.checked)" />
            <flux:label class="font-normal">{{ $defaultLabel }}</flux:label>
        </flux:field>
    @endif
</x-field>
