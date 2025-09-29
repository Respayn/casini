<th
    {{ $attributes->merge([
        'class' => 'bg-table-column px-2.5 py-1.5 text-white first:rounded-tl-sm last:rounded-tr-sm',
    ]) }}>
    <div class="flex items-center gap-2">
        {{ $slot }}
    </div>
</th>
