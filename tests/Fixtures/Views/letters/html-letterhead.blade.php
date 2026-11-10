<x-letter.page :header-height="40" :footer-height="20">
    <x-slot:header>
        <p>Letterhead Office</p>
    </x-slot:header>

    <x-slot:footer>
        <p>Letterhead Footer</p>
    </x-slot:footer>

    @foreach (range(1, 100) as $line)
        <p>Line {{ $line }}</p>
    @endforeach
</x-letter.page>
