<flux:sidebar.nav class="h-full">
    @foreach($entries as $entry)
        {{ $entry->render($style) }}
    @endforeach
</flux:sidebar.nav>
