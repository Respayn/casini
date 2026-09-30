@blaze

@props([
    'stacked' => false,
])

<th
    {{ $attributes->merge([
        'class' => 'bg-table-column px-2.5 py-1.5 text-white first:rounded-tl-sm last:rounded-tr-sm whitespace-nowrap',
    ]) }}>
    <div @class([
        'w-full',
        'block' => $stacked,
        'flex items-center gap-2' => ! $stacked,
    ])>
        {{ $slot }}
    </div>
</th>
