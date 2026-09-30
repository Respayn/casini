@php
    $clipId = 'yandex-metrika-logo-'.uniqid();
@endphp
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <clipPath id="{{ $clipId }}">
        <circle cx="10" cy="10" r="10" />
    </clipPath>
    <g clip-path="url(#{{ $clipId }})">
        <rect width="8" height="11" fill="#1A3C6C" />
        <rect y="11" width="8" height="9" fill="#FC3F1D" />
        <rect x="7" width="6" height="20" fill="#04C8C8" />
        <rect x="13" width="7" height="20" fill="#FFCC00" />
    </g>
</svg>
