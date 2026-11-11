<x-letter.page>
    @foreach (range(1, 100) as $line)
        <p>Line {{ $line }}</p>
    @endforeach
</x-letter.page>
