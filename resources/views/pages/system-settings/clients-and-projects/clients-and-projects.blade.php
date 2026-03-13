<div class="flex flex-col gap-3">
    <div class="flex justify-between items-center">
        <h1 class="text-primary-text text-xl font-semibold">Клиенты и Клиенто-проекты</h1>

        <div class="flex gap-2 items-center">
            <x-button.button label="+ Создать клиента" variant="primary" x-on:click="$dispatch('client-create')" />
            <x-button.button href="{{ route('system-settings.clients-and-projects.projects.manage') }}"
                label="+ Создать клиенто-проект" variant="primary" />
        </div>
    </div>
    <div>
        <x-data.table class="w-full">
            <x-data.table-columns>
                <x-data.table-column>
                    Клиент
                    <x-overlay.tooltip class="text-white">
                        Формируется на основе списка оплат в ДРС и добавленных клиентов
                    </x-overlay.tooltip>
                </x-data.table-column>
                <x-data.table-column>
                    ИНН
                    <x-overlay.tooltip>
                        С помощью ИНН мы можем автоматически определять операции по клиенту
                    </x-overlay.tooltip>
                </x-data.table-column>
                <x-data.table-column>
                    Клиенто-проект
                    <x-overlay.tooltip>
                        Клиенто-проекты привязываются клиенты в настройках клиенто-проекта
                    </x-overlay.tooltip>
                </x-data.table-column>
                <x-data.table-column>
                    Тип клиенто-проекта
                </x-data.table-column>
                <x-data.table-column>
                    Начальный баланс
                    <x-overlay.tooltip>
                        Поле учитывается при формировании сверки бюджетов, значение может быть как положительное (нам
                        должны), так и отрицательным (мы должны)
                    </x-overlay.tooltip>
                </x-data.table-column>
            </x-data.table-columns>

            <x-data.table-rows>
                <?php /** @var \App\Data\ClientData $client */ ?>
                @foreach ($this->clients as $clientIndex => $client)
                    @if ($client->projects->count())
                        @foreach($client->projects as $projectIndex => $project)
                            <x-data.table-row wire:key="client-{{ $clientIndex }}-project-{{ $projectIndex }}">
                                @if($projectIndex === 0)
                                    <x-data.table-cell :rowspan="count($client->projects)">
                                        <button
                                            wire:click="$dispatch('client-edit', { 
                                                id: {{ $client->id }},
                                                name: '{{ $client->name }}',
                                                inn: '{{ $client->inn }}',
                                                initialBalance: {{ $client->initial_balance }},
                                                managerId: {{ $client->manager_id }}
                                            })"
                                            class="link">
                                            {{ $client->name }}
                                        </button>
                                    </x-data.table-cell>
                                    <x-data.table-cell :rowspan="count($client->projects)">
                                        {{ $client->inn }}
                                    </x-data.table-cell>
                                @endif
                                <x-data.table-cell>
                                    <a href="{{ route('system-settings.clients-and-projects.projects.manage', $project->id) }}"
                                        class="link">
                                        {{ $project->name }}
                                    </a>
                                </x-data.table-cell>
                                <x-data.table-cell>
                                    {{ $project->project_type->label() }}
                                </x-data.table-cell>
                                <x-data.table-cell>
                                    {{ Number::currency($client->initial_balance, in: 'RUB', locale: 'ru') }}
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    @else
                        <x-data.table-row wire:key="client-{{ $clientIndex }}">
                            <x-data.table-cell>
                                <button wire:click="$dispatch('client-edit', { 
                                        id: {{ $client->id }},
                                        name: '{{ $client->name }}',
                                        inn: '{{ $client->inn }}',
                                        initialBalance: {{ $client->initial_balance }},
                                        managerId: {{ $client->manager_id }}
                                    })"
                                    class="link">
                                    {{ $client->name }}
                                </button>
                            </x-data.table-cell>
                            <x-data.table-cell>
                                {{ $client->inn }}
                            </x-data.table-cell>
                            <x-data.table-cell colspan="2">
                                Нет проектов
                            </x-data.table-cell>
                            <x-data.table-cell>
                                {{ Number::currency($client->initial_balance, in: 'RUB', locale: 'ru') }}
                            </x-data.table-cell>
                        </x-data.table-row>
                    @endif
                @endforeach
            </x-data.table-rows>
        </x-data.table>
    </div>

    <livewire:client.create-modal @clientSaved="$refresh" />
</div>