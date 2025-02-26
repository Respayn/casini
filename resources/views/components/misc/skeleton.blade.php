@props([
    'shape' => 'rectangle',
    'width' => '100%',
    'height' => '1rem',
    'size' => null,
])

@php
    if ($size) {
        $width = $size;
        $height = $size;
    }
@endphp

<div
    style="width: {{ $width }}; height: {{ $height }};"
    @class(['bg-gray-200 animate-pulse', 'rounded-full' => $shape === 'circle'])
>
</div>
