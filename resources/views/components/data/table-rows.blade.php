@props([
    'emptyMessage' => null
])

<tbody {{ $attributes }}>
    @php
        $renderedSlot = $slot->toHtml();

        $renderedSlotWithoutComments = preg_replace('/<!--(.|\s)*?-->/', '', $renderedSlot);

        $renderedSlotTrimmed = trim($renderedSlotWithoutComments);

        $isSlotEmpty = ($renderedSlotTrimmed === '');
    @endphp

    @if ($isSlotEmpty)
        <x-data.table-row>
            <x-data.table-cell colspan="1000" class="italic">
                {{ $emptyMessage ?? 'Данные отсутствуют' }}
            </x-data.table-cell>
        </x-data.table-row>
    @else
        {!! $renderedSlot !!}
    @endif
</tbody>
