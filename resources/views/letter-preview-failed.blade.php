<x-layouts::guest :title="__('letters/letters.print_failed')">
    <div class="mx-auto max-w-xl space-y-4 p-6">
        <flux:heading size="lg">{{ __('letters/letters.print_failed') }}</flux:heading>

        <ul class="list-disc space-y-1 ps-5">
            @foreach($messages as $message)
                <li><flux:text>{{ $message }}</flux:text></li>
            @endforeach
        </ul>
    </div>
</x-layouts::guest>
