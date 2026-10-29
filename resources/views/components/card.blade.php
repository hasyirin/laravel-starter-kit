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

    if ($accordion && $attributes->get('x-data') !== null) {
        throw new \InvalidArgumentException('An accordion card has its own x-data. Put your Alpine state on an element around the card.');
    }

    $hasHeading = $hasContent($heading);
    $hasSubheading = $hasContent($subheading);
    $hasActions = $hasContent($actions);
    $hasBody = $hasContent($slot);
    $hasFooter = $hasContent($footer) && ! $partAttributes($footer)->get('hidden');

    $accordion = $accordion && $hasHeading && ($hasBody || $hasFooter);
    $collapsed = $accordion && $collapsed;

    // until-found keeps a folded part searchable, so find in page and links to it can open the card. Livewire runs x-bind, not
    // x-effect, when it copies the card's state onto new HTML, so a fold survives updates. A folding part is inert, so focus stays out.
    $foldAttributes = $accordion ? array_filter([
        'hidden' => $collapsed ? 'until-found' : null,
        'x-bind:hidden' => "cardShown ? false : 'until-found'",
        'x-bind:inert' => '! cardOpen && cardShown',
    ]) : [];
@endphp

<flux:card
    {{ $attributes->except($accordion ? ['x-data'] : [])->merge($accordion ? ['x-data' => 'card', 'data-card-collapsed' => $collapsed ? 'true' : 'false'] : []) }}
>
    @if($hasHeading || $hasSubheading || $hasActions)
        <flux:card.header :class="$accordion ? 'group/card-header cursor-pointer' : null">
            @if($hasHeading)
                <flux:card.heading :attributes="$partAttributes($heading)">
                    @if($accordion)
                        <button
                            type="button"
                            class="group/card-toggle flex w-full cursor-pointer items-center justify-between gap-4 text-start"
                            aria-expanded="{{ $collapsed ? 'false' : 'true' }}"
                            x-bind:aria-expanded="cardOpen"
                            x-on:click="toggleCard"
                            data-card-toggle
                        >
                            <span class="flex min-w-0 items-center gap-2 [overflow-wrap:anywhere]">{{ $heading }}</span>

                            <flux:icon.chevron-down
                                variant="mini"
                                class="shrink-0 text-zinc-500 transition-transform duration-250 group-hover/card-header:text-zinc-800 group-aria-expanded/card-toggle:rotate-180 motion-reduce:transition-none dark:text-zinc-400 dark:group-hover/card-header:text-white"
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
        <flux:card.footer :attributes="$partAttributes($footer)->except(['hidden', ...array_keys($foldAttributes)])->merge($foldAttributes)">
            {{ $footer }}
        </flux:card.footer>
    @endif
</flux:card>
