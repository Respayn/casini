<?php

use Livewire\Component;

new class extends Component {

    public string $integrationCode;
    
}; ?>

<x-overlay.modal name="integration-settings-modal">
    @if ($integrationCode)
        <x-slot:body>
            <x-dynamic-component component="project-form.{{ $integrationCode }}-integration-modal-body" />
        </x-slot:body>
        
        <x-slot:sidebar>
            <x-dynamic-component component="project-form.{{ $integrationCode }}-integration-modal-sidebar" />
        </x-slot:sidebar>
    @endif
</x-overlay.modal>
