@props([
    'letterhead' => null,
    'placement' => null,
])

@php
    use App\Domain\Shared\Letter\Actions\FindLetterhead;
    use App\Domain\Shared\Letter\Enums\LetterheadImage;
    use App\Domain\Shared\Letter\Models\Letterhead;
    use App\Domain\Shared\Letter\Support\LetterheadView;

    if ($letterhead === null && $placement !== null) {
        $letterhead = FindLetterhead::make()->execute($placement);
    }

    $header = $letterhead instanceof Letterhead ? $letterhead->printableImage(LetterheadImage::Header) : null;
    $footer = $letterhead instanceof Letterhead ? $letterhead->printableImage(LetterheadImage::Footer) : null;
    $view = $letterhead instanceof LetterheadView ? $letterhead : null;

    $headerHeight = 25;
    $firstHeaderHeight = $header['heightMm'] ?? 25;
    $footerHeight = $footer['heightMm'] ?? 25;

    if ($view !== null && $view->everyPage) {
        $headerHeight = $view->heightMm;
    }

    if ($view !== null) {
        $firstHeaderHeight = $view->heightMm;
    }
@endphp

<x-letter.page :header-height="$headerHeight" :first-header-height="$firstHeaderHeight" :footer-height="$footerHeight">
    @if($header !== null)
        <x-slot:firstHeader :src="$header['src']"></x-slot:firstHeader>
    @endif

    @if($footer !== null)
        <x-slot:firstFooter :src="$footer['src']"></x-slot:firstFooter>
    @endif

    @if($view !== null && $view->everyPage)
        <x-slot:header>
            @include($view->view)
        </x-slot:header>
    @elseif($view !== null)
        <x-slot:firstHeader>
            @include($view->view)
        </x-slot:firstHeader>
    @endif

    {{ $slot }}
</x-letter.page>
