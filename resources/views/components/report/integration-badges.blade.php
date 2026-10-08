@props([
    'items' => [],
])

@php
    $items = $items instanceof \Illuminate\Support\Collection ? $items : collect($items ?? []);
@endphp

@if ($items->isEmpty())
    -
@else
    <div class="grid w-max max-w-full grid-cols-2 gap-1">
        @foreach ($items as $tool => $count)
            <x-badge
                icon="logo.{{ $tool }}"
                iconClasses="size-4 shrink-0"
                class="justify-center gap-1 px-1 py-0.5 text-xs font-bold"
            >
                {{ $count }}
            </x-badge>
        @endforeach
    </div>
@endif
