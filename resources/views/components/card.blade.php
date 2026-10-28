@props([
    'heading' => null,
    'subheading' => null,
    'actions' => null,
    'footer' => null,
    'bodyClass' => null,
    'accordion' => false,
    'collapsed' => false,
])

@php
    // Livewire wraps a false @if in comment markers, so a slot counts only when it has more than comments.
    $hasContent = fn (mixed $part): bool => $part instanceof \Illuminate\View\ComponentSlot ? $part->hasActualContent() : $part !== false && filled($part);

    $partAttributes = fn (mixed $part): \Illuminate\View\ComponentAttributeBag => $part instanceof \Illuminate\View\ComponentSlot
        ? $part->attributes
        : new \Illuminate\View\ComponentAttributeBag;

    $hasHeading = $hasContent($heading);
    $hasSubheading = $hasContent($subheading);
    $hasActions = $hasContent($actions);
    $hasBody = $hasContent($slot);
    $hasFooter = $hasContent($footer);

    $accordion = $accordion && $hasHeading;
    $collapsed = $accordion && $collapsed;

    if ($accordion && $attributes->has('x-data')) {
        throw new \InvalidArgumentException('An accordion card has its own x-data. Put your Alpine state on an element around the card.');
    }

    // until-found keeps a folded part searchable, so find in page and links to it can open the card.
    $foldAttributes = $accordion ? array_filter([
        'hidden' => $collapsed ? 'until-found' : null,
        'x-effect' => "cardShown ? \$el.removeAttribute('hidden') : \$el.setAttribute('hidden', 'until-found')",
    ]) : [];
@endphp

<flux:card {{ $attributes->merge($accordion ? ['x-data' => 'card', 'data-card-collapsed' => $collapsed ? 'true' : 'false'] : []) }}>
    @if($hasHeading || $hasSubheading || $hasActions)
        <flux:card.header>
            @if($hasHeading)
                <flux:card.heading :attributes="$partAttributes($heading)">
                    @if($accordion)
                        <button
                            type="button"
                            class="group/card-toggle flex w-full cursor-pointer items-center justify-between gap-4 text-start after:absolute after:inset-0"
                            aria-expanded="{{ $collapsed ? 'false' : 'true' }}"
                            x-bind:aria-expanded="cardOpen"
                            x-on:click="toggleCard"
                            data-card-toggle
                        >
                            <span>{{ $heading }}</span>

                            <flux:icon.chevron-down
                                variant="mini"
                                class="shrink-0 text-zinc-300 transition-transform duration-250 group-hover/card-toggle:text-zinc-800 group-aria-expanded/card-toggle:rotate-180 motion-reduce:transition-none dark:text-zinc-400 dark:group-hover/card-toggle:text-white"
                            />
                        </button>
                    @else
                        {{ $heading }}
                    @endif
                </flux:card.heading>
            @endif

            @if($hasSubheading)
                <flux:card.subheading :attributes="$partAttributes($subheading)">{{ $subheading }}</flux:card.subheading>
            @endif

            @if($hasActions)
                <flux:card.actions :attributes="$partAttributes($actions)">{{ $actions }}</flux:card.actions>
            @endif
        </flux:card.header>
    @endif

    @if($hasBody)
        <flux:card.body :attributes="new \Illuminate\View\ComponentAttributeBag($foldAttributes)" :class="$bodyClass">{{ $slot }}</flux:card.body>
    @endif

    @if($hasFooter)
        <flux:card.footer :attributes="$partAttributes($footer)->merge($foldAttributes)">{{ $footer }}</flux:card.footer>
    @endif
</flux:card>
