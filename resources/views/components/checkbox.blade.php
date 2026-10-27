@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'options' => null,
    'variant' => 'default',
    'all' => null,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $name = $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('name');

    // Flux puts a prop that a variant does not use on the checkbox as an attribute, which breaks on HTML.
    // So only the variants that show icons get the icon, and the default variant, which also puts the label there, gets the label as text.
    $showsIcons = in_array($variant, ['buttons', 'cards'], true);
@endphp

@if($options === null && $slot->isEmpty())
    <flux:field
        variant="inline"
        {{ $attributes->only('class')->class(['grid-cols-[auto_1fr]', '[&:has([data-input-control][disabled])>[data-input-label]]:opacity-50']) }}
    >
        <flux:checkbox :$required :class="$inputClass" data-input-control {{ $attributes->except('class') }} />

        @if(filled($label))
            <div class="flex items-baseline" data-input-label>
                <flux:label :$badge>{{ $label }}</flux:label>

                @if($required)
                    <span class="ms-0.5 text-sm leading-tight font-medium text-red-500 dark:text-red-400" aria-hidden="true">*</span>
                @endif
            </div>
        @endif

        @if(filled($description))
            <flux:description>{{ $description }}</flux:description>
        @endif

        @unless($noErrorMessage)
            <flux:error :$name />
        @endunless
    </flux:field>
@else
    <x-field :$label :$description :$descriptionTrailing :$hint :$actions :$badge :$required :$name :$noErrorMessage {{ $attributes->only('class') }}>
        <flux:checkbox.group :$variant :$required :class="$inputClass" data-input-control {{ $attributes->except('class') }}>
            @if($all)
                <flux:checkbox.all :label="is_string($all) ? $all : __('common/inputs.select_all')" />
            @endif

            @foreach(\App\Livewire\Components\Option::collect($options ?? []) as $option)
                <flux:checkbox
                    :value="$option->value()"
                    :label="$variant === 'default' ? $option->resolvedText() : $option->resolvedLabel()"
                    :description="$option->resolvedDescription()"
                    :icon="$showsIcons ? $option->resolvedIcon() : null"
                    :disabled="$option->isDisabled()"
                    wire:key="option-{{ $option->value() }}"
                />
            @endforeach

            {{ $slot }}
        </flux:checkbox.group>
    </x-field>
@endif
