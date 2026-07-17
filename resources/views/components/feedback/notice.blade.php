@props([
    'variant' => 'info',
])

@php
    $variantClasses = match ($variant) {
        'info' => 'border-primary bg-blue-50 text-primary-text',
        'success' => 'border-green-200 bg-green-100 text-green-700',
        'error' => 'border-[#FF7373] bg-[#FFF5F5] text-[#FF7373]',
        default => 'border-primary bg-blue-50 text-primary-text',
    };
@endphp

<div {{ $attributes->class([
    'mb-4 break-words rounded-lg border p-4 text-sm',
    $variantClasses,
]) }}>
    {{ $slot }}
</div>
