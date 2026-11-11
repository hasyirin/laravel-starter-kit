@props(['letter'])

@if($letter->signerName !== null)
    <div {{ $attributes->class('mt-8 break-inside-avoid') }}>
        @if($letter->computerSigned)
            <p class="mb-2 text-xs italic">{{ __('letters/letters.computer_signed') }}</p>
        @else
            <div class="h-20"></div>
        @endif

        <p class="font-bold">{{ $letter->signerName }}</p>

        @if($letter->signerPosition !== null)
            <p>{{ $letter->signerPosition }}</p>
        @endif
    </div>
@endif
