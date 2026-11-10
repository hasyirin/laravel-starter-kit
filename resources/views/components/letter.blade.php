@props(['letter'])

<x-letterhead :letter="$letter">
    <div class="text-[11pt] leading-relaxed [counter-reset:letter-item]">
        <div class="mb-6 text-right">
            @if($letter->referenceNumber !== null)
                <p>{{ $letter->referenceNumber }}</p>
            @endif

            <p>{{ $letter->formattedDate }}</p>
        </div>

        {{ $slot }}
    </div>
</x-letterhead>
