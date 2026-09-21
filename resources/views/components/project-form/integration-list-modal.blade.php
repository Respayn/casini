@props(['name', 'title', 'integrations', 'canEdit' => true])

<x-overlay.modal
    name="{{ $name }}"
    title="{{ $title }}"
>
    <x-slot:body>
        <div class="flex flex-col gap-1">
            @foreach ($integrations as $integration)
                @if ($canEdit)
                    <x-button.button
                        :label="$integration->name"
                        wire:click="selectIntegration('{{ $integration->code }}')"
                    ></x-button.button>
                @else
                    <x-permissions.field-guard :enabled="false">
                        <x-button.button
                            :label="$integration->name"
                            disabled
                        ></x-button.button>
                    </x-permissions.field-guard>
                @endif
            @endforeach
        </div>
    </x-slot:body>
</x-overlay.modal>
