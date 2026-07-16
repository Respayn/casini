@props(['params'])

@php
    $name = $params['name'] ?? '—';
    $id = $params['id'] ?? null;
@endphp

<x-data.table-cell {{ $attributes }}>
    @if ($id)
        <a
            class="text-primary underline whitespace-nowrap"
            href="{{ route('system-settings.users.edit', [
                'user' => $id,
            ]) }}"
            wire:navigate
        >
            {{ $name }}
        </a>
    @else
        <span class="whitespace-nowrap">{{ $name }}</span>
    @endif
</x-data.table-cell>
