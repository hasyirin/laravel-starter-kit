@php
    $action = $getState();
@endphp

<div class="flex flex-col px-3 py-3">
    @if($action instanceof \App\Domain\Shared\Actor\Models\Action)
        @if(filled($action->actor_name))
            <flux:text variant="strong" class="font-bold uppercase">{{ $action->actor_name }}</flux:text>
        @endif

        @if(filled($action->position_name))
            <flux:text size="sm" class="font-semibold uppercase">{{ $action->position_name }}</flux:text>
        @endif

        <flux:text size="sm" variant="subtle" class="font-semibold">
            {{ \App\Domain\User\Support\Preferences::current()->datetime($action->acted_at) }}
        </flux:text>
    @endif
</div>
