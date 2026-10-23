@php
    use App\Domain\Shared\Organization\Models\Organization;

    $org = Organization::owner();
@endphp

<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('layouts.partials.auth.head')
    </head>

    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="relative grid h-dvh items-center justify-center px-8 sm:px-0 lg:grid-cols-2">
            <div class="relative hidden h-full flex-col bg-neutral-900 p-10 text-white lg:flex dark:border-e dark:border-neutral-800">
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url({{ asset('images/auth/split/bg.webp') }});">
                    <div class="h-full w-full opacity-40" style="background-image: url({{ asset('images/auth/split/abstract.webp') }});"></div>
                </div>
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-md">
                        <x-app-logo-icon class="me-2 h-7 fill-current text-white" />
                    </span>
                    {{ config('app.name', 'Laravel') }}
                </a>

                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-2">
                        <footer>
                            <flux:heading>{{ $org->name }} &copy; {{ now()->year }}</flux:heading>
                        </footer>
                    </blockquote>
                </div>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-87.5">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current dark:text-white" />
                        </span>

                        <span>{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        @vite('resources/js/app.js')
        @livewireScriptConfig
    </body>
</html>
