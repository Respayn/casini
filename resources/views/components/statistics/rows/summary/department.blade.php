@props(['params'])

@php
    use App\Enums\ProjectType;
@endphp

<x-data.table-cell class="bg-table-summary-bg font-bold" {{ $attributes }}>
    <div class="flex flex-col">
        <span class="whitespace-nowrap">Контекст: {{ $params[ProjectType::CONTEXT_AD->value] }}</span>
        <span class="whitespace-nowrap">SEO: {{ $params[ProjectType::SEO_PROMOTION->value] }}</span>
    </div>
</x-data.table-cell>