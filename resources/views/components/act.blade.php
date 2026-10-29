@props([
    'name' => null,
    'action' => null,
])

@php
    $name ??= $action?->name;

    if (is_string($name)) {
        $name = \App\Domain\Shared\Actor\Enums\Act::from($name);
    }

    $action?->loadMissing($action->profile_id === null ? ['actor'] : ['profile.user', 'profile.position']);

    $position = $action?->profile?->position?->name;

    $actedAt = \App\Domain\User\Support\Preferences::current()->datetime($action?->acted_at);
@endphp

@if($name)
    <dl {{ $attributes->class('grid gap-2') }}>
        <dt class="text-sm font-medium text-zinc-800 dark:text-white">{{ $name->byLabel() }}:</dt>

        <dd class="ms-4 flex flex-col">
            @if($action)
                <flux:text variant="strong" class="font-bold uppercase">{{ $action->profile?->name ?? $action->actor?->name }}</flux:text>

                @if(filled($position))
                    <flux:text size="sm" class="font-semibold uppercase">{{ $position }}</flux:text>
                @endif

                <flux:text size="sm" variant="subtle" class="font-semibold">{{ $actedAt }}</flux:text>
            @else
                {{ $slot }}
            @endif
        </dd>
    </dl>
@endif
