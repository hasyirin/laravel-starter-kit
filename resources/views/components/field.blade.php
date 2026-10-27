@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'hint' => null,
    'actions' => null,
    'badge' => null,
    'required' => false,
    'name' => null,
    'noErrorMessage' => false,
])

@php
    $descriptionTrailing ??= $attributes->pluck('description:trailing');
@endphp

<flux:field
    {{ $attributes->class([
        '[&>[data-input-label-row]+[data-flux-description]]:-mt-1!',
        '[&>[data-input-label-row]+[data-flux-description]]:mb-1!',
        '[&>[data-flux-error]]:mt-0!',
        '[&>*:not([data-input-label-row])+[data-flux-description]]:mt-0!',
        '[&:has([data-input-control][disabled])>[data-input-label-row]>[data-input-label]]:opacity-50',
    ]) }}
>
    @if(filled($label) || filled($hint) || filled($actions))
        <div class="flex items-center gap-x-3" data-input-label-row>
            @if(filled($label))
                <div class="flex items-baseline" data-input-label>
                    <flux:label :$badge>{{ $label }}</flux:label>

                    @if($required)
                        <span class="ms-0.5 text-sm leading-tight font-medium text-red-500 dark:text-red-400" aria-hidden="true">*</span>
                    @endif
                </div>
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

    {{ $slot }}

    @unless($noErrorMessage)
        <flux:error :$name />
    @endunless

    @if(filled($descriptionTrailing))
        <flux:description>{{ $descriptionTrailing }}</flux:description>
    @endif
</flux:field>
