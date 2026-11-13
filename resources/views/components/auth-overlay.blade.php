@props([
    'id' => uniqid(),
])

<svg {{ $attributes }} aria-hidden="true">
    <defs>
        <pattern id="grid-{{ $id }}" width="40" height="40" patternUnits="userSpaceOnUse">
            <path d="M40 .5H.5V40" fill="none" stroke="white" stroke-opacity="0.15" />
        </pattern>
        <radialGradient id="fade-{{ $id }}" cx="50%" cy="45%" r="65%">
            <stop offset="0" stop-color="white" />
            <stop offset="1" stop-color="black" />
        </radialGradient>
        <mask id="mask-{{ $id }}">
            <rect width="100%" height="100%" fill="url(#fade-{{ $id }})" />
        </mask>
        <linearGradient id="shade-{{ $id }}" x2="0" y2="1">
            <stop offset="0" stop-color="#171717" stop-opacity="0.76" />
            <stop offset="0.3" stop-color="#171717" stop-opacity="0.4" />
            <stop offset="0.7" stop-color="#171717" stop-opacity="0.4" />
            <stop offset="1" stop-color="#171717" stop-opacity="0.75" />
        </linearGradient>
    </defs>
    <rect width="100%" height="100%" fill="url(#grid-{{ $id }})" mask="url(#mask-{{ $id }})" />
    <rect width="100%" height="100%" fill="url(#shade-{{ $id }})" />
</svg>
