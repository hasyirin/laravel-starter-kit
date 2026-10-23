<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('layouts.partials.app.head')
    </head>

    <body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
        @include('layouts.partials.app.sidebar')
        @include('layouts.partials.app.header')

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @livewire('notifications')

        @filamentScripts
        @fluxScripts
        @vite('resources/js/app.js')
        @livewireScriptConfig
    </body>
</html>
