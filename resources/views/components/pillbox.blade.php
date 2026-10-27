@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'options' => [],
    'variant' => 'default',
    'searchable' => null,
    'searchModel' => null,
    'searchPlaceholder' => null,
    'inputClass' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
    $name = $attributes->whereStartsWith('wire:model')->first() ?? $attributes->get('name');

    if ($searchModel !== null && $variant !== 'default') {
        throw new \InvalidArgumentException("search-model needs the default variant, not [{$variant}].");
    }

    $options = \App\Livewire\Components\Option::collect($options);
    $searchable = $searchModel !== null || ($searchable ?? $variant === 'default');

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

<x-field :$label :$description :$descriptionTrailing :$hint :$actions :$badge :$required :$name :$noErrorMessage {{ $attributes->only('class') }}>
    <flux:pillbox :$variant :$searchable multiple :$required :class="$inputClass" data-input-control :attributes="$controlAttributes">
        @if($searchModel !== null)
            <x-slot:search>
                <flux:pillbox.search :attributes="$searchAttributes" />
            </x-slot:search>
        @endif

        @foreach($options as $option)
            @php
                $optionLabel = $option->resolvedLabel();
                $optionIcon = $option->resolvedIcon();
                $optionDescription = $option->resolvedDescription();
                $optionBadge = $option->resolvedBadge();
                $isRich = filled($optionIcon) || filled($optionDescription) || filled($optionBadge);
            @endphp

            {{-- A pill shows the text of its option, so a rich option names the pill with its label. --}}
            <flux:pillbox.option
                :value="$option->value()"
                :selected-label="$option->resolvedSelectedLabel() ?? ($isRich ? $option->resolvedText() : null)"
                :disabled="$option->isDisabled()"
            >
                @if($isRich)
                    <div class="flex min-w-0 items-start">
                        @if(is_string($optionIcon))
                            <flux:icon :icon="$optionIcon" variant="mini" class="me-2 text-zinc-400 dark:text-white/60" />
                        @elseif(filled($optionIcon))
                            {{ $optionIcon }}
                        @endif

                        <div class="flex min-w-0 flex-col gap-1">
                            <div>
                                {{ $optionLabel }}

                                @if(filled($optionBadge))
                                    <flux:badge :color="$option->resolvedColor()" size="sm" class="ms-1.5">{{ $optionBadge }}</flux:badge>
                                @endif
                            </div>

                            @if(filled($optionDescription))
                                <flux:text class="text-xs">{{ $optionDescription }}</flux:text>
                            @endif
                        </div>
                    </div>
                @else
                    {{ $optionLabel }}
                @endif
            </flux:pillbox.option>
        @endforeach

        {{ $slot }}
    </flux:pillbox>
</x-field>
