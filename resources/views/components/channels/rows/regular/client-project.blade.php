@props(['params'])

<x-data.table-cell {{ $attributes }}>
    <a
        class="text-primary underline"
        href="{{ route('system-settings.clients-and-projects.projects.manage', [
            'projectId' => $params['id'],
        ]) }}"
        wire:navigate
    >
        {{ isset($params['name']) ? $params['name'] : '-' }}
    </a>
</x-data.table-cell>
