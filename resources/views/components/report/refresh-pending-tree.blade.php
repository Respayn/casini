@props([
    'projects' => [],
])

@php
    /** @var list<array{id: int, label: string, group: ?string}> $projects */
    $projects = is_array($projects) ? $projects : [];
    $grouped = [];
    foreach ($projects as $project) {
        $group = filled($project['group'] ?? null) ? (string) $project['group'] : '';
        $grouped[$group][] = $project;
    }
@endphp

@if ($projects !== [])
    <div {{ $attributes->class(['mt-3 rounded-lg border border-flat-border bg-secondary/40 p-4']) }}>
        <div class="text-primary-text mb-3 text-sm font-semibold">
            Обновляются клиенто-проекты
        </div>
        <div class="flex flex-col gap-3">
            @foreach ($grouped as $groupLabel => $items)
                <div>
                    @if ($groupLabel !== '')
                        <div class="text-caption-text mb-1 text-xs font-semibold uppercase">
                            {{ $groupLabel }}
                        </div>
                    @endif
                    <ul class="flex flex-col gap-1">
                        @foreach ($items as $item)
                            <li
                                wire:key="refresh-pending-{{ $item['id'] }}"
                                class="text-primary-text text-sm"
                            >
                                {{ $item['label'] }}
                                <span class="text-caption-text text-xs">(№{{ $item['id'] }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
@endif
