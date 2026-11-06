<flux:header class="dark block! border-b border-zinc-200 shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
    <div class="flex flex-wrap items-center">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        @hasSection('header')
            <flux:navbar scrollable class="order-last basis-full lg:order-0 lg:flex-1">
                @yield('header')
            </flux:navbar>
        @endif

        <flux:navbar class="ms-auto -mt-1 -mb-1 shrink-0">
            <x-user-menu />
        </flux:navbar>
    </div>
</flux:header>
