<div class="space-y-2">
    @foreach($prints as $print)
        <div class="flex items-center justify-between gap-4">
            <a href="{{ $print['url'] }}" class="font-medium underline">{{ $print['name'] }}</a>
            <flux:text size="sm">{{ $print['printer'] }}, {{ $print['printed_at'] }}</flux:text>
        </div>
    @endforeach
</div>
