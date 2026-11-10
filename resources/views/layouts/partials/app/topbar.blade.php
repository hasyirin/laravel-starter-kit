@use(App\Livewire\Components\Navigation\Style)

<div class="[grid-area:header]">
    <flux:header container class="dark border-b border-zinc-200 shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <x-app-logo href="{{ route('dashboard') }}" wire:navigate class="me-4 max-lg:hidden" />

        <livewire:layouts.app.navigation :style="Style::Topbar" />

        <flux:navbar class="ms-auto -mt-1 -mb-1 shrink-0">
            <x-user-menu />
        </flux:navbar>
    </flux:header>

    @hasSection('header')
        <flux:header container class="dark border-b border-zinc-200 dark:border-zinc-700 dark:bg-zinc-950">
            <flux:navbar scrollable>
                @yield('header')
            </flux:navbar>
        </flux:header>
    @endif
</div>

<flux:sidebar sticky collapsible="mobile" class="dark border-e lg:hidden dark:border-zinc-700 dark:bg-zinc-950">
    <flux:sidebar.header>
        <x-app-logo sidebar href="{{ route('dashboard') }}" wire:navigate />
        <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <livewire:layouts.app.navigation />
</flux:sidebar>
