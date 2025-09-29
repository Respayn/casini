<div class="fixed flex h-screen max-h-screen min-w-[355px] max-w-[355px] flex-col bg-white pe-[30px] ps-5 pt-[39px]">
    <div class="mb-[60px] flex">
        <a href="{{ route('channels') }}">
            <x-icons.logo />
        </a>
    </div>

    <x-form.input-text
        class="mb-5"
        label="Поиск:"
        icon="icons.search"
        wire:model.live.debounce="searchQuery"
        placeholder="Начните вводить"
    />

    <div class="mb-4">
        <x-button.button
            variant="link"
            label="Все клиенты"
            icon="icons.client"
            wire:click="resetSelectedProject"
        />
    </div>

    <x-form.select
        label="Сортировать по:"
        :options="$sortOptions"
        wire:model.live="sortBy"
    />

    {{-- Список сотрудников --}}
    <div
        class="pretty-scroll mb-4 mr-[-25px] flex-1 overflow-y-auto"
        style="scrollbar-gutter: stable"
    >
        <ul
            class="pr-[15px]"
            x-cloak
        >
            @foreach ($employees as $employeeKey => $employee)
                <li
                    class="flex flex-col pb-2"
                    x-data="{
                        employeeOpen: $wire.entangle('employees.{{ $employeeKey }}.open')
                    }"
                    wire:key="sidebar-employee-{{ $employee->id }}"
                >
                    {{-- Информация о сотруднике --}}
                    <div
                        class="flex min-h-[42px] cursor-pointer items-center justify-between rounded-[5px] p-[10px]"
                        x-on:click="employeeOpen = !employeeOpen"
                        x-bind:class="{
                            'bg-primary text-white': employeeOpen,
                            'bg-secondary text-primary-text': !employeeOpen
                        }"
                    >
                        <div class="flex items-center gap-[10px]">
                            <span>
                                <x-icons.card />
                            </span>
                            <span x-bind:class="employeeOpen && 'font-extrabold'">{{ $employee->name }}</span>
                        </div>
                        <span>
                            <x-icons.arrow x-show="!employeeOpen" />
                            <x-icons.minus x-show="employeeOpen" />
                        </span>
                    </div>

                    {{-- Клиенты --}}
                    <ul
                        class="flex flex-col text-sm ps-4"
                        x-show="employeeOpen"
                        x-collapse
                    >
                        @foreach ($employee->clients as $clientKey => $client)
                            {{-- Клиент --}}
                            <li
                                class="relative mt-1 treeitem first:mt-2"
                                x-data="{
                                    clientOpen: $wire.entangle('employees.{{ $employeeKey }}.clients.{{ $clientKey }}.open')
                                }"
                                wire:key="sidebar-client-{{ $client->id }}"
                            >
                                <div class="arrow"></div>
                                {{-- Информация о клиенте --}}
                                <div
                                    class="flex min-h-[42px] cursor-pointer items-center justify-between rounded-[5px] p-[10px]"
                                    x-on:click="clientOpen = !clientOpen"
                                    x-bind:class="{
                                        'bg-flat-primary text-white': clientOpen,
                                        'bg-secondary text-primary-text': !clientOpen
                                    }"
                                >
                                    <span class="font-bold">{{ $client->name }}</span>
                                    <span>
                                        <x-icons.plus x-show="!clientOpen" />
                                        <x-icons.minus x-show="clientOpen" />
                                    </span>
                                </div>

                                {{-- Проекты --}}
                                @if (!empty($client->projects))
                                    <div
                                        class="relative flex flex-col ps-4"
                                        x-show="clientOpen"
                                        x-collapse
                                    >
                                        @foreach ($client->projects as $project)
                                            <div
                                                class="treeitem border-flat-border relative mt-1 flex min-h-[42px] cursor-pointer items-center gap-1 rounded-[5px] border p-[10px] first:mt-2"
                                                x-on:click="$wire.set('selectedProjectId', {{ $project->id }}).live"
                                                x-bind:class="{
                                                    'bg-selected-project-card *:text-white': $wire.selectedProjectId ==
                                                        {{ $project->id }}
                                                }"
                                                wire:key="sidebar-project-{{ $project->id }}"
                                            >
                                                <div class="arrow"></div>
                                                <span
                                                    class="font-semibold text-primary-text">{{ $project->name }}</span>
                                                <span class="text-xs text-input-text">(№{{ $project->id }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </div>
</div>
