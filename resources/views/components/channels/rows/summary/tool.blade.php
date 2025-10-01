@props(['params'])

@php
    if (is_array($params)) {
        $params = collect($params);
    }
@endphp

<x-data.table-cell class="bg-table-summary-bg" {{ $attributes }}>
    @if($params->isEmpty())
        -
    @else
        @foreach ($params as $tool => $count)
            <x-badge icon="logo.{{ $tool }}" class="gap-2 font-bold">
                {{ $count }}
            </x-badge>
        @endforeach
    @endif
</x-data.table-cell>