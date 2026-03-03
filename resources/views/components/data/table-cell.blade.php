@props([
    'full' => false
])

<td
    {{ $attributes->class(['border-table-cell border px-2.5 py-3.5']) }}
    {{ $attributes }}
    @if($full) colspan="1000" @endif
>
    {{ $slot }}
</td>
