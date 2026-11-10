@php
    $draft = context()->getHidden('letter_draft') === true;
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src data:; style-src 'unsafe-inline'; font-src data:" />
        <style>
            {{ str(Vite::content('resources/css/letter.css'))->toHtmlString() }}
        </style>
    </head>
    <body class="font-sans text-sm text-black">
        @if($draft)
            <div class="pointer-events-none fixed inset-0 flex items-center justify-center">
                <span class="-rotate-45 text-9xl font-bold tracking-widest text-gray-300 uppercase opacity-50">{{ __('letters/letters.draft') }}</span>
            </div>
        @endif

        {{ $slot }}
    </body>
</html>
