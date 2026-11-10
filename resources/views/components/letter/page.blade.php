@props([
    'headerHeight' => 25,
    'firstHeaderHeight' => 25,
    'footerHeight' => 25,
])

@php
    $draft = context()->getHidden('letter_draft') === true;

    $headerImage = isset($header) ? $header->attributes->get('src') : null;
    $headerHtml = isset($header) && $headerImage === null ? $header : null;
    $firstHeaderImage = isset($firstHeader) ? $firstHeader->attributes->get('src') : null;
    $firstHeaderHtml = isset($firstHeader) && $firstHeaderImage === null ? $firstHeader : null;
    $footerImage = isset($footer) ? $footer->attributes->get('src') : null;
    $footerHtml = isset($footer) && $footerImage === null ? $footer : null;
    $firstFooterImage = isset($firstFooter) ? $firstFooter->attributes->get('src') : null;

    if ($firstHeaderHtml !== null && $headerHtml !== null) {
        throw new \LogicException('A letter page cannot have an HTML first header and an HTML header. Give the letterhead one mode: first page only or every page.');
    }

    if (isset($firstFooter) && $firstFooterImage === null) {
        throw new \LogicException('A letter page cannot have an HTML first footer. A first-page-only footer must be an image.');
    }
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src data:; style-src 'unsafe-inline'; font-src data:" />
        <style>
            {{ str(Vite::content('resources/css/letter.css'))->toHtmlString() }}
        </style>
        @if($headerImage)
            <style>
                @page {
                    margin-top: {{ $headerHeight }}mm;

                    @top-center {
                        content: '';
                        background: url('{{ $headerImage }}') top / 100% auto no-repeat;
                    }
                }
            </style>
        @elseif($headerHtml)
            <style>
                @page {
                    margin-top: 0;
                }
            </style>
        @endif

        @if($footerImage)
            <style>
                @page {
                    margin-bottom: calc({{ $footerHeight }}mm + 15mm);

                    @bottom-center {
                        background: url('{{ $footerImage }}') bottom / 100% auto no-repeat;
                    }
                }
            </style>
        @endif

        @if($firstHeaderImage)
            <style>
                @page :first {
                    margin-top: {{ $firstHeaderHeight }}mm;

                    @top-center {
                        content: '';
                        background: url('{{ $firstHeaderImage }}') top / 100% auto no-repeat;
                    }
                }
            </style>
        @elseif($firstHeaderHtml)
            <style>
                @page :first {
                    margin-top: 0;

                    @top-center {
                        content: none;
                    }
                }
            </style>
        @endif

        @if($firstFooterImage)
            <style>
                @page :first {
                    margin-bottom: calc({{ $footerHeight }}mm + 15mm);

                    @bottom-center {
                        background: url('{{ $firstFooterImage }}') bottom / 100% auto no-repeat;
                    }
                }
            </style>
        @endif
    </head>
    <body class="mx-[20mm] font-sans text-sm text-black">
        @if($draft)
            <div class="pointer-events-none fixed inset-0 flex items-center justify-center">
                <span class="-rotate-45 text-9xl font-bold tracking-widest text-gray-300 uppercase opacity-50">{{ __('letters/letters.draft') }}</span>
            </div>
        @endif

        @if($headerHtml)
            <div class="fixed inset-x-0 top-0 overflow-hidden" style="height: {{ $headerHeight }}mm">{{ $headerHtml }}</div>
        @endif

        @if($footerHtml)
            <div class="fixed inset-x-0 bottom-0 overflow-hidden" style="height: {{ $footerHeight }}mm">{{ $footerHtml }}</div>
        @endif

        @if($firstHeaderHtml)
            <div class="-mx-[20mm] overflow-hidden" style="height: {{ $firstHeaderHeight }}mm">{{ $firstHeaderHtml }}</div>
        @endif

        @if($headerHtml || $footerHtml)
            <table class="w-full border-collapse">
                @if($headerHtml)
                    <thead>
                        <tr>
                            <td class="p-0" style="height: {{ $headerHeight }}mm"></td>
                        </tr>
                    </thead>
                @endif

                @if($footerHtml)
                    <tfoot>
                        <tr>
                            <td class="p-0" style="height: {{ $footerHeight }}mm"></td>
                        </tr>
                    </tfoot>
                @endif

                <tbody>
                    <tr>
                        <td class="p-0">{{ $slot }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            {{ $slot }}
        @endif
    </body>
</html>
