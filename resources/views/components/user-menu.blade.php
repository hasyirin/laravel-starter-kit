@use(App\Domain\User\Support\Impersonation)
@use(App\Http\Enums\Area)

@php
    $impersonator = Impersonation::impersonator();
@endphp

<flux:dropdown position="bottom" align="start">
    <button
        type="button"
        class="group [ui-dropdown>&]:w-full max-lg:[&>span]:hidden flex items-center rounded-lg p-1 hover:bg-zinc-800/5 dark:hover:bg-white/10"
        data-test="user-menu-button"
    >
        <flux:avatar size="sm" :name="auth()->user()->name" :initials="auth()->user()->initials()" class="shrink-0" />

        <span class="mx-2 grid min-w-0 text-start leading-tight">
            <span class="truncate text-sm font-medium text-zinc-500 group-hover:text-zinc-800 dark:text-white/80 dark:group-hover:text-white">
                {{ auth()->user()->name }}
            </span>
            @if($impersonator)
                <span class="truncate text-xs text-zinc-400 dark:text-white/60" data-test="user-menu-impersonator">{{ $impersonator->name }}</span>
            @endif
        </span>

        <div class="ms-auto flex size-8 shrink-0 items-center justify-center">
            <flux:icon.chevrons-up-down variant="micro" class="text-zinc-400 group-hover:text-zinc-800 dark:text-white/80 dark:group-hover:text-white" />
        </div>
    </button>

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                @if($impersonator)
                    <flux:text size="sm" class="truncate">{{ $impersonator->name }}</flux:text>
                @endif
            </div>
        </div>
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route(Area::current()->route('settings'))" icon="cog" wire:navigate>{{ __('navigation.settings') }}</flux:menu.item>

            @if(Impersonation::active())
                <form method="POST" action="{{ route('impersonation.stop') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-uturn-left" class="w-full cursor-pointer" data-test="stop-impersonating-button">
                        {{ __('user/users.impersonation.stop') }}
                    </flux:menu.item>
                </form>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                    {{ __('common/buttons.log_out') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
