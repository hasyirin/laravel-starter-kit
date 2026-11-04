@props([
    'label',
])

<dl {{ $attributes->class('grid content-start gap-1') }}>
    <dt class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ $label }}</dt>

    <dd class="flex flex-wrap items-center gap-2 text-sm text-zinc-800 dark:text-zinc-100">{{ $slot }}</dd>
</dl>
