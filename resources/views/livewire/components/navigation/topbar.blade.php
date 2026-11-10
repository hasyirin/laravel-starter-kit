<flux:navbar class="-mb-px flex-1 max-lg:hidden">
    @foreach($entries as $entry)
        {{ $entry->render($style) }}
    @endforeach
</flux:navbar>
