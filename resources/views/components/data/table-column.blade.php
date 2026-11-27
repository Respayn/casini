@props([
    'stacked' => false,
    'wrap' => false,
])

<th
    {{ $attributes->class([
        'bg-table-column px-2.5 py-1.5 text-white first:rounded-tl-sm last:rounded-tr-sm',
        'whitespace-nowrap' => ! $wrap,
        'whitespace-normal' => $wrap,
    ]) }}>
    <div @class([
        'w-full',
        'block' => $stacked || $wrap,
        'flex items-center gap-2' => ! $stacked && ! $wrap,
    ])>
        {{ $slot }}
    </div>
</th>
