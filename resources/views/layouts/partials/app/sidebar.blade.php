<flux:sidebar sticky collapsible class="dark border-e dark:border-zinc-700 dark:bg-zinc-950">
    <flux:sidebar.header>
        <x-app-logo sidebar href="{{ route('dashboard') }}" wire:navigate />
        <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <livewire:layouts.app.sidebar />
</flux:sidebar>
