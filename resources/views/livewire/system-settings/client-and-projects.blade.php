<div class="flex flex-col gap-3">
    <div class="flex justify-between items-center">
        <h1 class="text-primary-text text-xl font-semibold">Клиенты и Клиенто-проекты</h1>

        <div class="flex gap-2 items-center">
            <!-- Здесь будет логика для отображения списка клиентов -->
            <a href="{{ route('system-settings.clients-and-projects.clients.create') }}" class="btn inline-flex items-center justify-center bg-primary text-white hover:bg-primary-dark rounded-lg px-4 py-2">
                + Создать клиента
            </a>
            <!-- Здесь будет логика для отображения списка клиенто-проектов -->
            <a href="{{ route('system-settings.clients-and-projects.projects.create') }}" class="btn inline-flex items-center justify-center bg-primary text-white hover:bg-primary-dark rounded-lg px-4 py-2">
                + Создать клиенто-проект
            </a>
        </div>
    </div>
    <div>
        <x-data.table>
            <x-data.table-columns>
                <x-data.table-column>
                    Клиент
                    <x-overlay.tooltip class="text-white">
                        Формируется на основе списка оплат в ДРС и добавленных клиентов
                    </x-overlay.tooltip>
                </x-data.table-column>
                <x-data.table-column>
                    Инн
                    <x-overlay.tooltip class="text-white">
                        С помощью ИНН мы можем автоматически определять операции по клиенту
                    </x-overlay.tooltip>
                </x-data.table-column>
                <x-data.table-column>
                    Клиенто-проект
                    <x-overlay.tooltip class="text-white">
                        Клиенто-проекты привязываются клиенты в настройках клиенто-проекта
                    </x-overlay.tooltip>
                </x-data.table-column>
                <x-data.table-column>
                    Тип клиенто-проекта
                </x-data.table-column>
                <x-data.table-column>
                    Начальный баланс
                    <x-overlay.tooltip class="text-white">
                        Поле учитывается при формировании сверки бюджетов, значение может быть как положительное (нам должны), так и отрицательным (мы должны)
                    </x-overlay.tooltip>
                </x-data.table-column>
            </x-data.table-columns>

            <x-data.table-rows>
                <?php /** @var \App\Data\ClientData $client */ ?>
                @foreach ($clients as $index => $client)
                    <x-data.table-row wire:key="key-0">
                        <x-data.table-cell>
                            {{ $client->name }}
                        </x-data.table-cell>
                        <x-data.table-cell>
                            {{ $client->inn }}
                        </x-data.table-cell>
                        <x-data.table-cell>
                            <?php /** @var \App\Data\ProjectData $project */ ?>
                            @foreach($client->projects as $projectKey => $project)
                                <x-data.table-cell wire:key="project-{{$projectKey}}">
                                    {{ $project->name }}
                                </x-data.table-cell>
                            @endforeach
                        </x-data.table-cell>
                        <x-data.table-cell>
                        </x-data.table-cell>
                        <x-data.table-cell>
                        </x-data.table-cell>
                    </x-data.table-row>
                @endforeach
            </x-data.table-rows>
        </x-data.table>
    </div>
</div>
