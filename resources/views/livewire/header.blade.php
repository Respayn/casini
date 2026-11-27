<div class="w-full rounded-bl-2xl rounded-br-2xl bg-white py-[10px] pe-[20px] ps-[15px]">
    <div class="flex items-center justify-between">
        @php
            use App\Support\SystemSettingsSectionPermissions;

            $settingsRoute = SystemSettingsSectionPermissions::firstAccessibleSettingsRouteName();
            $isOnSystemSettings = request()->is('system-settings', 'system-settings/*');
            $canOpenReports = auth()->user()?->can('read reports')
                || auth()->user()?->can('edit reports')
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
                <a
                    href="{{ route($settingsRoute) }}"
                    wire:navigate
                    title="Настройки"
                    aria-label="Настройки"
                    @class([
                        'inline-flex h-10 w-10 items-center justify-center rounded-md transition',
                        'text-primary' => $isOnSystemSettings,
                        'text-secondary-text hover:text-primary' => ! $isOnSystemSettings,
                    ])
                >
                    <x-icons.settings-applications />
                </a>
            @endif
            {{-- tick заставляет Livewire перерисовать колокольчик после notifications-changed --}}
            <div wire:key="notifications-bell-{{ $notificationsTick }}">
                <x-notifications.bell-button />
            </div>

            @php
                $headerUser = Auth::user();
                $headerFirstName = trim((string) ($headerUser->first_name ?? ''));
                // Фамилия необязательна: пустую и пробелы не показываем.
                $headerLastName = trim((string) ($headerUser->last_name ?? ''));
                $headerFullName = collect([$headerFirstName, $headerLastName])
                    ->filter(fn (string $part): bool => $part !== '')
                    ->implode(' ');
                if ($headerFirstName !== '' && $headerLastName !== '') {
                    $headerInitials = mb_strtoupper(
                        mb_substr($headerFirstName, 0, 1).mb_substr($headerLastName, 0, 1)
                    );
                } elseif ($headerFirstName !== '') {
                    // Без фамилии: одна или две первые буквы имени.
                    $headerInitials = mb_strtoupper(mb_substr($headerFirstName, 0, min(2, mb_strlen($headerFirstName))));
                } elseif ($headerLastName !== '') {
                    $headerInitials = mb_strtoupper(mb_substr($headerLastName, 0, min(2, mb_strlen($headerLastName))));
                } else {
                    $headerInitials = '?';
                }
                $headerRole = $headerUser->roles->first()?->display_name;
            @endphp
            <div x-data="{ open: false }" class="relative ml-6 flex w-[230px] max-w-[230px] items-center">
                <!-- Клик по этой зоне открывает / закрывает меню -->
                <div
                    @click="open = !open"
                    class="flex min-w-0 w-full cursor-pointer items-center select-none"
                    title="{{ $headerFullName }}"
                >
                    @if ($headerUser->image_path)
                        <img
                            class="h-10 w-10 shrink-0 rounded-full object-cover"
                            src="{{ Storage::url($headerUser->image_path) }}"
                            alt="{{ $headerFullName }}"
                        >
                    @else
                        <div
                            class="bg-primary text-body flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                            aria-hidden="true"
                        >{{ $headerInitials }}</div>
                    @endif
                    <div class="ml-2.5 min-w-0">
                        <div class="truncate font-semibold">{{ $headerFullName }}</div>
                        <div class="truncate text-xs text-gray-400">{{ $headerRole }}</div>
                    </div>
                </div>

                <!-- Меню -->
                <ul x-show="open" @click.away="open = false" x-transition
                    class="absolute left-0 top-full z-50 mt-2 w-full overflow-hidden rounded-b-lg bg-white shadow-lg text-nowrap"
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
