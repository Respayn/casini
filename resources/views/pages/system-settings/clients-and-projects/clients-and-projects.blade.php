<div class="flex flex-col gap-3">
    <div class="flex items-center justify-between">
        <h1 class="text-primary-text text-xl font-semibold">Клиенты и Клиенто-проекты</h1>

        <div class="flex items-center gap-2">
            <x-button.button
                label="+ Создать клиента"
                variant="primary"
                x-on:click="$dispatch('client-create')"
            />
            <x-button.button
                href="{{ route('system-settings.clients-and-projects.projects.manage') }}"
                label="+ Создать клиенто-проект"
                variant="primary"
            />
        </div>
    </div>

    <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
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
                <?php /** @var \Src\Application\Clients\GetClientsWithProjects\ClientDto $client */ ?>
                @foreach ($this->clients as $clientIndex => $client)
                    @php
                        $clientEditPayload = Js::from([
                            'id' => $client->id,
                            'name' => $client->name,
                            'inn' => $client->inn,
                            'initialBalance' => $client->initialBalance,
                            'managerId' => $client->managerId,
                        ]);

                        $projectCount = count($client->projects);
                    @endphp

                    @if ($projectCount > 0)
                        <?php /** @var \Src\Application\Clients\GetClientsWithProjects\ClientProjectDto $project */ ?>
                        @foreach ($client->projects as $project)
                            <x-data.table-row wire:key="client-{{ $client->id }}-project-{{ $project->id }}">
                                @if ($loop->first)
                                    <x-data.table-cell :rowspan="$projectCount">
                                        <button
                                            class="link"
                                            x-on:click="$dispatch('client-edit', {{ $clientEditPayload }})"
                                        >
                                            {{ $client->name }}
                                        </button>
                                    </x-data.table-cell>
                                    <x-data.table-cell :rowspan="$projectCount">
                                        {{ $client->inn }}
                                    </x-data.table-cell>
                                @endif

                                <x-data.table-cell>
                                    <a
                                        class="link"
                                        href="{{ route('system-settings.clients-and-projects.projects.manage', $project->id) }}"
                                    >
                                        {{ $project->name }}
                                    </a>
                                </x-data.table-cell>
                                <x-data.table-cell>
                                    {{ $project->projectType }}
                                </x-data.table-cell>

                                @if ($loop->first)
                                    <x-data.table-cell :rowspan="$projectCount">
                                        {{ Number::currency($client->initialBalance, in: 'RUB', locale: 'ru') }}
                                    </x-data.table-cell>
                                @endif
                            </x-data.table-row>
                        @endforeach
                    @else
                        <x-data.table-row wire:key="client-{{ $client->id }}">
                            <x-data.table-cell>
                                <button
                                    class="link"
                                    wire:click="$dispatch('client-edit', {{ $clientEditPayload }})"
                                >
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
                                {{ Number::currency($client->initialBalance, in: 'RUB', locale: 'ru') }}
                            </x-data.table-cell>
                        </x-data.table-row>
                    @endif
                @endforeach
            </x-data.table-rows>
        </x-data.table>
    </x-panel.scroll-panel>

    <livewire:client.create-modal @clientSaved="$refresh" />
</div>
