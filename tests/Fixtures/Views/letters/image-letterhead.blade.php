<x-letter.page :header-height="34" :first-header-height="34" :footer-height="34">
    @if ($everyPage)
        <x-slot:header :src="$image"></x-slot:header>
        <x-slot:footer :src="$image"></x-slot:footer>
    @else
        <x-slot:firstHeader :src="$image"></x-slot:firstHeader>
        <x-slot:firstFooter :src="$image"></x-slot:firstFooter>
    @endif

    @foreach (range(1, 100) as $line)
        <p>Line {{ $line }}</p>
    @endforeach
</x-letter.page>
