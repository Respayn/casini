@props([
    'open' => false
])

<div x-data="{ isOpen: @json($open) }">
    {{ $slot }}
</div>
