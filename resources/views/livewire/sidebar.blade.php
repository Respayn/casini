<div
    x-data
    x-init="
        document.documentElement.classList.remove('sidebar-animating');
        $store.sidebar.syncDom();
    "
>
    {{-- Панель фиксированной ширины: сдвиг через html.sidebar-collapsed (см. app.css) --}}
    <aside
        class="sidebar-panel fixed top-0 left-0 z-20 flex h-screen max-h-screen w-[355px] min-w-[355px] max-w-[355px] flex-col bg-white pe-[30px] ps-5 pt-[39px] will-change-transform"
        x-bind:aria-hidden="! $store.sidebar.open"
    >
        <div class="mb-[60px] flex flex-col gap-2">
            <a href="{{ route('channels') }}" class="flex flex-col gap-2">
                <x-icons.logo />
                <x-brand.tagline />
            </a>
        </div>

        <div
            class="mb-5"
            wire:loading.delay.long.class="pointer-events-none opacity-60"
            wire:target="searchQuery,sortBy,clearFilters"
        >
            <x-form.input-text
                label="Поиск по клиентам и клиенто-проектам"
                icon="icons.search"
                wire:model.live.debounce.200ms="searchQuery"
                placeholder="Начните вводить"
            />
        </div>

        <div class="mb-4">
            <x-button.button
                variant="secondary"
                label="Удалить фильтры"
                icon="icons.delete"
                :disabled="! $this->canClearFilters"
                wire:click="clearFilters"
            />
        </div>

        <div
            wire:loading.delay.long.class="pointer-events-none opacity-60"
            wire:target="searchQuery,sortBy,clearFilters"
        >
            <x-form.select
                label="Собрать портфель клиенто-проектов по:"
                :options="$sortOptions"
                wire:model.live="sortBy"
            />
        </div>

        @if ($sortOptions === [])
            <p class="text-secondary-text mt-4 text-sm">
                Включите «Собрать портфель клиенто-проектов» у ролей в настройках
            </p>
        @endif

        {{-- Скелетон: hidden по умолчанию (Tailwind), снимается только на время wire:loading --}}
        <div
            class="pretty-scroll sidebar-tree-scroll relative mt-5 mb-4 mr-[-25px] min-h-[200px] flex-1 overflow-y-auto"
        >
            <div
                class="absolute inset-0 z-10 overflow-hidden bg-white"
                wire:loading.delay.long
                wire:target="searchQuery,sortBy,clearFilters"
            >
                <x-sidebar.tree-skeleton class="h-full" />
            </div>

            @if ($searchQuery !== '' && $employees === [])
                <p class="text-caption-text pr-[15px] pt-2 text-sm">
                    Нет результатов
                </p>
            @else
                <ul class="pr-[15px]">
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
                                                            wire:click="selectProject({{ $project->id }})"
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
                @endif
        </div>
    </aside>

    <button
        type="button"
        class="sidebar-toggle bg-primary fixed top-[52px] z-30 flex h-12 w-[18px] cursor-pointer items-center justify-center rounded-full border-0 shadow-sm"
        x-on:click="$store.sidebar.toggle()"
        x-bind:title="$store.sidebar.open ? 'Свернуть меню' : 'Развернуть меню'"
        x-bind:aria-label="$store.sidebar.open ? 'Свернуть меню' : 'Развернуть меню'"
        x-bind:aria-expanded="$store.sidebar.open"
    >
        <span class="sidebar-toggle-icon block">
            <svg
                width="6"
                height="10"
                viewBox="0 0 6 10"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
            >
                <path
                    d="M5.2 1.2L1.4 5l3.8 3.8"
                    stroke="white"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </span>
    </button>
</div>
