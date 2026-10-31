@props([
    'name' => null,
    'action' => null,
])

@php
    $name = $action?->name ?? $name;

    if (is_string($name)) {
        $name = \App\Domain\Shared\Actor\Enums\Act::from($name);
    }

    if ($action?->profile_id === null) {
        $action?->setRelation('profile', null)->loadMissing(['actor' => fn ($actor) => $actor->constrain([\App\Domain\User\Models\User::class => fn ($query) => $query->withTrashed()->without('profile')])]);
    } else {
        $action->loadMissing(['profile.user' => fn ($user) => $user->without('profile'), 'profile.position']);
    }

    $actorName = $action?->profile?->name ?? ($action?->actor instanceof \App\Domain\Shared\Actor\Contracts\Models\HasActorName ? $action->actor->getActorName() : null);

    $position = $action?->profile?->position?->name;

    $actedAt = \App\Domain\User\Support\Preferences::current()->datetime($action?->acted_at);
@endphp

@if($name)
    <dl {{ $attributes->class('grid gap-2') }}>
        <dt class="text-sm font-medium text-zinc-800 dark:text-white">{{ $name->byLabel() }}:</dt>

        <dd class="ms-4 flex flex-col">
            @if($action)
                @if(filled($actorName))
                    <flux:text variant="strong" class="font-bold uppercase">{{ $actorName }}</flux:text>
                @endif

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
