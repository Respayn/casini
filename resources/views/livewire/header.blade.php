<div class="w-full rounded-bl-2xl bg-white py-[10px] pe-[20px] ps-[15px]">
    <div class="flex items-center justify-between">
        @php
            use App\Support\SystemSettingsSectionPermissions;

            $settingsRoute = SystemSettingsSectionPermissions::firstAccessibleSettingsRouteName();
            $isOnSystemSettings = request()->is('system-settings', 'system-settings/*');
            $canOpenReports = auth()->user()?->can('read reports')
                || auth()->user()?->can('full reports');
            $showBackToReports = $canOpenReports && $isOnSystemSettings;
        @endphp
        <div class="flex items-center gap-4">
            <livewire:system-settings.agency.agency-switcher-component />
            <x-overlay.modal name="agency-modal" title="Создать агентство">
                <x-slot:body>
                    <livewire:system-settings.agency.create-agency-form :key="'create-agency-modal'" />
                </x-slot:body>
            </x-overlay.modal>
            @if ($showBackToReports)
                <x-button.button
                    href="{{ route('reports') }}"
                    label="Вернуться к отчетам"
                    icon="icons.arrow-left"
                    size="none"
                    variant="link"
                    class="text-secondary-text inline-flex max-h-[26px] items-center gap-3 text-[18px]"
                />
            @endif
        </div>
        <div class="flex items-center">
            @if ($settingsRoute)
                <x-button.button
                    href="{{ route($settingsRoute) }}"
                    icon="icons.gear"
                    :variant="$isOnSystemSettings ? 'primary' : 'outlined'"
                    rounded
                    @class([
                        'hover:bg-primary hover:text-white' => ! $isOnSystemSettings,
                        'hover:!bg-primary hover:!text-white' => $isOnSystemSettings,
                    ])
                />
            @endif
            <x-notifications.bell-button />

            <div x-data="{ open: false }" class="ml-6 flex items-center relative">
                <!-- Клик по этой зоне открывает / закрывает меню -->
                <div @click="open = !open" class="flex items-center cursor-pointer select-none min-w-[230px]">
                    @if (Auth::user()->image_path)
                        <div style="width: 40px; height: 40px;">
                            <img class="rounded-full" src="{{ Storage::url(Auth::user()->image_path) }}" />
                        </div>
                    @else
                        <x-misc.skeleton shape="circle" size="40px" />
                    @endif
                    <div class="ml-2.5">
                        <div class="font-semibold">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                        <div class="text-xs text-gray-400">{{ Auth::user()->role ?? 'Администратор' }}</div>
                    </div>
                </div>

                <!-- Меню -->
                <ul x-show="open" @click.away="open = false" x-transition
                    class="absolute left-0 top-full mt-2 bg-white rounded-b-lg shadow-lg overflow-hidden z-50 w-full text-nowrap"
                    style="display: none;">
                    <!-- Пункт: Настройки профиля -->
                    <li>
                        <a href="{{ route('system-settings.users.edit', Auth::user()->id) }}"
                            class="flex items-center py-4 px-4 hover:bg-gray-100 transition gap-1">
                            <x-icons.gear-profile class="h-[17px] w-[17px]" />
                            <span class="ml-2 text-sm text-gray-700 flex-0">Настройки профиля</span>
                        </a>
                    </li>

                    <!-- Пункт: Выйти -->
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="flex items-center w-full px-4 py-2 hover:bg-gray-100 transition text-left cursor-pointer gap-1">
                                <x-icons.out class="h-[17px] w-[17px]" />
                                <span class="ml-2 text-sm text-gray-700 flex-0">Выйти</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
