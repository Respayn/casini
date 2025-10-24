@props(['params'])

<x-data.table-cell {{ $attributes }}>
    @empty($params)
        -
    @else
        @foreach ($params as $tool => $count)
            <x-badge icon="logo.{{ $tool }}" class="gap-2 font-bold">
                {{ $count }}
            </x-badge>
        @endforeach
    @endempty
</x-data.table-cell>