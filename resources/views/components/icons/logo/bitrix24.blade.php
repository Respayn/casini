@php
    $clipId = 'bitrix24-logo-'.uniqid();
@endphp
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <clipPath id="{{ $clipId }}">
        <circle cx="10" cy="10" r="10" />
    </clipPath>
    <g clip-path="url(#{{ $clipId }})">
        <rect width="20" height="20" fill="#2FC6F6" />
        <path d="M5.5 10.4L8.4 13.3L14.5 6.7" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
    </g>
</svg>
