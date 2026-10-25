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

<flux:field
    :x-data="$case !== null || $hasDefault ? 'inputField' : null"
    :data-input-case="$case"
    :data-input-default="$hasDefault ? (string) $default : null"
    :data-input-model="$hasDefault ? $model : null"
    {{ $attributes->only('class')->class([
        'content-start',
        '[&>[data-input-label-row]+[data-flux-description]]:-mt-1!',
        '[&>[data-input-label-row]+[data-flux-description]]:mb-1!',
        '[&>[data-flux-error]]:mt-0!',
        '[&>*:not([data-input-label-row])+[data-flux-description]]:mt-0!',
        '[&:has([data-input-control][disabled])>[data-input-label-row]>[data-flux-label]]:opacity-50',
    ]) }}
>
    @if(filled($label) || filled($hint) || filled($actions))
        <div class="flex items-center gap-x-3" data-input-label-row>
            @if(filled($label))
                <flux:label :$badge>
                    {{ $label }}
                    @if($required)
                        <span class="ms-0.5 text-red-500 dark:text-red-400" aria-hidden="true">*</span>
                    @endif
                </flux:label>
            @endif

            @if(filled($hint) || filled($actions))
                <div class="-my-1 ms-auto flex items-center gap-x-2">
                    @if(filled($hint))
                        <span class="text-sm text-zinc-500 dark:text-white/60" data-input-hint>{{ $hint }}</span>
                    @endif

                    @if(filled($actions))
                        <div class="flex items-center gap-x-1" data-input-actions>{{ $actions }}</div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if(filled($description))
        <flux:description>{{ $description }}</flux:description>
    @endif

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

    @unless($noErrorMessage)
        <flux:error :$name />
    @endunless

    @if(filled($descriptionTrailing))
        <flux:description>{{ $descriptionTrailing }}</flux:description>
    @endif
</flux:field>
