<div class="flex flex-col gap-3">
    <div class="flex justify-between items-center">
        <h1 class="text-primary-text text-xl font-semibold">Клиенты и Клиенто-проекты</h1>

        <div class="flex gap-2 items-center">
            <x-button.button label="+ Создать клиента" variant="primary" wire:click="initClientForm" />
            <x-button.button href="{{ route('system-settings.clients-and-projects.projects.manage') }}" label="+ Создать клиенто-проект" variant="primary" />
        </div>
    </div>
    <div>
        <x-data.table class="w-full">
            <x-data.table-columns>
                <!-- Ваши заголовки колонок -->
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
                        Поле учитывается при формировании сверки бюджетов, значение может быть как положительное (нам должны), так и отрицательным (мы должны)
                    </x-overlay.tooltip>
                </x-data.table-column>
            </x-data.table-columns>

            <x-data.table-rows>
                <?php /** @var \App\Data\ClientData $client */ ?>
                @foreach ($clients as $clientIndex => $client)
                    @if ($client->projects->count())
                        @foreach($client->projects as $projectIndex => $project)
                            <x-data.table-row wire:key="client-{{ $clientIndex }}-project-{{ $projectIndex }}">
                                @if($projectIndex === 0)
                                    <x-data.table-cell :rowspan="count($client->projects)">
                                        <button wire:click="initClientForm({{ $clientIndex }})" class="link">
                                            {{ $client->name }}
                                        </button>
                                    </x-data.table-cell>
                                    <x-data.table-cell :rowspan="count($client->projects)">
                                        {{ $client->inn }}
                                    </x-data.table-cell>
                                @endif
                                <x-data.table-cell>
                                    <a href="{{ route('system-settings.clients-and-projects.projects.manage', $project->id) }}" class="link">
                                        {{ $project->name }}
                                    </a>
                                </x-data.table-cell>
                                <x-data.table-cell>
                                    {{ $project->project_type->label() }}
                                </x-data.table-cell>
                                <x-data.table-cell>
                                    {{ '-' }}
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    @else
                        <x-data.table-row wire:key="client-{{ $clientIndex }}">
                            <x-data.table-cell>
                                <button wire:click="initClientForm({{ $clientIndex }})" class="link">
                                    {{ $client->name }}
                                </button>
                            </x-data.table-cell>
                            <x-data.table-cell>
                                {{ $client->inn }}
                            </x-data.table-cell>
                            <x-data.table-cell colspan="3">
                                Нет проектов
                            </x-data.table-cell>
                        </x-data.table-row>
                    @endif
                @endforeach
            </x-data.table-rows>
        </x-data.table>
    </div>
    
    <x-overlay.modal
        name="client-modal"
        title="{{ $activeClientIndex === null ? 'Создание' : 'Редактирование' }}  клиента"
    >
        <x-slot:body>
            <x-form.form :is-normalized="true" wire:submit.prevent="saveClient" class="min-w-[723px]">
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="Заполните название клиента, так клиент будет отображаться во всех продуктах. Обязательное поле для заполнени"
                    >Клиент</x-form.form-label>
                    <div>
                        <x-form.input-text wire:model="clientForm.name"></x-form.input-text>
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="С помощью ИНН мы можем автоматически определять операции по клиенту"
                    >ИНН</x-form.form-label>
                    <div>
                        <x-form.input-text placeholder="-" wire:model="clientForm.inn"></x-form.input-text>
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="Выберите менеджера, все клиенто-проекты этого клиента будут привязаны к этому менеджеру"
                    >Менеджер</x-form.form-label>
                    <div>
                        <x-form.select
                            placeholder="Не выбрано"
                            :options='
                                $managers->map(function ($item) {
                                    $name = trim("{$item->first_name} {$item->last_name}");
                                    return
                                    [
                                        "label" => empty($name) ? $item->login : $name,
                                        "value" => $item->id
                                    ];
                                })->values()->all()'
                            wire:model="clientForm.manager"
                            class="w-full"
                        ></x-form.select>
                    </div>
                </x-form.form-field>
                <x-form.form-field>
                    <x-form.form-label
                        class="self-baseline"
                        required
                        tooltip="Поле учитывается при формировании сверки бюджетов, значение может быть как положительное (мы должны), так и отрицательным (нам должны)"
                    >Начальная статистика взаиморасчетов</x-form.form-label>
                    <div>
                        <x-form.input-text type="number" wire:model="clientForm.initial_balance"></x-form.input-text>
                    </div>
                </x-form.form-field>
                <div class="flex justify-between">
                    <x-button.button
                        icon="icons.check"
                        variant="primary"
                        type="submit"
                    >
                        <x-slot:label>
                            {{ $activeClientIndex === null ? 'Создать' : 'Сохранить' }}
                        </x-slot>
                    </x-button.button>

                    <x-button.button wire:click="$dispatch('modal-hide', { name: 'client-modal' })">
                        <x-slot:label>
                            Отменить
                        </x-slot>
                    </x-button.button>
                </div>
            </x-form.form>
        </x-slot:body>
    </x-overlay.modal>
</div>
