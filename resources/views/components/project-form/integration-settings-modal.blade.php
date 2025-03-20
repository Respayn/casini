@props([
    'integrationCode' => null,
])

@php
    $formattedIntegrationCode = Str::kebab(Str::camel($integrationCode));
@endphp

<x-overlay.modal name="integration-settings-modal">
    @if ($integrationCode)
        <x-slot:body>
            <x-dynamic-component component="project-form.{{ $formattedIntegrationCode }}-integration-modal-body" />
        </x-slot:body>

        <x-slot:sidebar>
            <x-dynamic-component component="project-form.{{ $formattedIntegrationCode }}-integration-modal-sidebar" />
        </x-slot:sidebar>
    @endif
</x-overlay.modal>
