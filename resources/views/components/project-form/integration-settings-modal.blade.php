@props([
    'projectIntegration' => null,
])

@php
    $formattedIntegrationCode = Str::kebab(Str::camel($projectIntegration?->integration->code));
@endphp

<x-overlay.modal
    name="integration-settings-modal"
    title="{{ $projectIntegration?->integration->name }}"
>
    @if ($projectIntegration)
        <x-slot:body>
            <x-dynamic-component
                component="project-form.{{ $formattedIntegrationCode }}-integration-modal-body"
                :project-integration="$projectIntegration"
            />
        </x-slot:body>

        <x-slot:sidebar>
            <x-dynamic-component component="project-form.{{ $formattedIntegrationCode }}-integration-modal-sidebar" />
        </x-slot:sidebar>
    @endif
</x-overlay.modal>
