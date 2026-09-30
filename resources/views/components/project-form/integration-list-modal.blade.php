@props(['name', 'title', 'integrations', 'canEdit' => true, 'disabledReasons' => []])

<x-overlay.modal
    name="{{ $name }}"
    title="{{ $title }}"
>
    <x-slot:body>
        <div class="flex flex-col gap-1">
            @foreach ($integrations as $integration)
                @php
                    $disabledReason = $disabledReasons[$integration->code] ?? null;
                    $isBlocked = filled($disabledReason);
                @endphp

                @if (! $canEdit)
                    <x-permissions.field-guard :enabled="false">
                        <x-button.button
                            :label="$integration->name"
                            disabled
                        ></x-button.button>
                    </x-permissions.field-guard>
                @elseif ($isBlocked)
                    <x-overlay.tooltip panel-max-width="22rem">
                        <x-slot:trigger>
                            <span class="inline-block w-full">
                                <x-button.button
                                    :label="$integration->name"
                                    :disabled="true"
                                />
                            </span>
                        </x-slot:trigger>
                        {{ $disabledReason }}
                    </x-overlay.tooltip>
                @else
                    <x-button.button
                        :label="$integration->name"
                        wire:click="selectIntegration('{{ $integration->code }}')"
                    ></x-button.button>
                @endif
            @endforeach
        </div>
    </x-slot:body>
</x-overlay.modal>
