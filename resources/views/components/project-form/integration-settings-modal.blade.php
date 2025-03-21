@props([
    'integration' => null,
])

@php
    $formattedIntegrationCode = Str::kebab(Str::camel($integration?->code));
@endphp

<x-overlay.modal name="integration-settings-modal" title="{{ $integration?->name }}">
    @if ($integration)
        <x-slot:body>
            <x-dynamic-component component="project-form.{{ $formattedIntegrationCode }}-integration-modal-body" />
        </x-slot:body>

        <x-slot:sidebar>
            <x-dynamic-component component="project-form.{{ $formattedIntegrationCode }}-integration-modal-sidebar" />
        </x-slot:sidebar>
    @endif
</x-overlay.modal>
