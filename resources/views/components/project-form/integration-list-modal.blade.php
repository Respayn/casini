@props(['name', 'title', 'integrations'])

<x-overlay.modal
    name="{{ $name }}"
    title="{{ $title }}"
>
    <x-slot:body>
        <div class="flex flex-col gap-1">
            @foreach ($integrations as $integration)
                <x-button.button
                    :label="$integration->name"
                    wire:click="selectIntegration('{{ $integration->code }}')"
                ></x-button.button>
            @endforeach
        </div>
    </x-slot:body>
</x-overlay.modal>
