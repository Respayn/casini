@props(['params'])

<x-data.table-cell {{ $attributes->class(['min-w-28']) }}>
    <x-report.integration-badges :items="$params" />
</x-data.table-cell>
