<div class="w-full rounded-bl-2xl bg-white py-[10px] pe-[20px] ps-[15px]">
    <div class="flex items-center justify-between">
        <div>
            <livewire:system-settings.agency.agency-switcher-component />
            <x-overlay.modal
                name="agency-modal"
                title="Создать агентство"
            >
                <x-slot:body>
                    <livewire:system-settings.agency.create-agency-form :key="'create-agency-modal'"/>
                </x-slot:body>
            </x-overlay.modal>
        </div>
        <div class="flex items-center">
            <x-button.button
                href="{{ route('system-settings.dictionaries') }}"
                icon="icons.gear"
                variant="outlined"
                rounded
                wire:navigate
            />

            <div
                x-data="{ open: false }"
                class="ml-6 flex items-center relative"
            >
                <!-- Клик по этой зоне открывает / закрывает меню -->
                <div
                    @click="open = !open"
                    class="flex items-center cursor-pointer select-none min-w-[230px]"
                >
                    <x-misc.skeleton
                        shape="circle"
                        size="40px"
                    />
                    <div class="ml-2.5">
                        <div class="font-semibold">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                        <div class="text-xs text-gray-400">{{ Auth::user()->role ?? 'Администратор' }}</div>
                    </div>
                </div>

                <!-- Меню -->
                <ul
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute left-0 top-full mt-2 bg-white rounded-b-lg shadow-lg overflow-hidden z-50 w-full text-nowrap"
                    style="display: none;"
                >
                    <!-- Пункт: Настройки профиля -->
                    <li>
                        <a
                            href="/"
                            class="flex items-center py-4 px-4 hover:bg-gray-100 transition gap-1"
                        >
                            <x-icons.gear-profile class="h-[17px] w-[17px]"/>
                            <span class="ml-2 text-sm text-gray-700 flex-0">Настройки профиля</span>
                        </a>
                    </li>

                    <!-- Пункт: Выйти -->
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="flex items-center w-full px-4 py-2 hover:bg-gray-100 transition text-left cursor-pointer gap-1"
                            >
                                <x-icons.out class="h-[17px] w-[17px]"/>
                                <span class="ml-2 text-sm text-gray-700 flex-0">Выйти</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
