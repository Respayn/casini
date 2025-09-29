@props(['params'])

@php
    $name = $params['name'];
    $id = $params['id'];
@endphp

<x-data.table-cell {{ $attributes }}>
    <a
        class="text-primary underline whitespace-nowrap"
        href="{{ route('system-settings.users.edit', [
            'user' => $id,
        ]) }}"
        wire:navigate
    >
        {{ $name }}
    </a>
</x-data.table-cell>
