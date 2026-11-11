@props(['letter'])

@if($letter->carbonCopies !== [])
    <div {{ $attributes->class('mt-8 break-inside-avoid text-sm') }}>
        <p class="font-bold">{{ __('letters/letters.carbon_copies') }}</p>

        <ul>
            @foreach($letter->carbonCopies as $copy)
                <li>{{ $copy->name }}</li>
            @endforeach
        </ul>
    </div>
@endif
