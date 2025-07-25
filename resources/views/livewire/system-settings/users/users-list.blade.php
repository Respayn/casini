<div class="flex flex-col gap-3">
    <div class="flex justify-between items-center">
        <div class="flex gap-4 items-center">
            <h1 class="text-primary-text text-xl font-semibold">Пользователи и роли</h1>

            <div class="flex items-center gap-3">
                <label class="inline-flex items-center">
                    <span class="mr-2 text-[14px]">Показывать только активные</span>
                    <x-form.checkbox wire:model="onlyActive"/>
                </label>
            </div>
        </div>

        <div class="flex gap-2 items-center">
            <a href="{{ route('system-settings.users.create') }}">
                <x-button.button label="+ Добавить пользователя" variant="primary" />
            </a>
        </div>
    </div>

    <x-data.table class="w-full">
        <x-data.table-columns>
            <x-data.table-column>ID</x-data.table-column>
            <x-data.table-column>Логин</x-data.table-column>
            <x-data.table-column>Фамилия и имя</x-data.table-column>
            <x-data.table-column>Роль</x-data.table-column>
            <x-data.table-column>Статус</x-data.table-column>
            <x-data.table-column>
                Ставка (₽)
                <x-overlay.tooltip>Последняя активная ставка пользователя</x-overlay.tooltip>
            </x-data.table-column>
        </x-data.table-columns>

        <x-data.table-rows>
            @forelse ($users as $user)
                <x-data.table-row wire:key="user-{{ $user['id'] }}">
                    <x-data.table-cell>
                        {{ $user['id'] }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <a href="{{ route('system-settings.users.edit', ['user' => $user['id']]) }}" class="link">
                            {{ $user['login'] }}
                        </a>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ $user['last_name'] }} {{ $user['first_name'] }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ \App\Enums\Role::tryFrom($user['roles'][0] ?? '')?->label() ?? '-' }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <div class="flex justify-center">
                            <span
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full font-medium
                            {{ $user['is_active'] ? 'bg-green-50 text-green-700' : 'bg-red-100 text-red-700' }}"
                            >
                            @if($user['is_active'])
                                    <x-icons.play class="w-4 h-4 mr-1 text-green-500" />
                                    Активный
                                @else
                                    <x-icons.pause class="w-4 h-4 mr-1 text-red-500" />
                                    Неактивный
                                @endif
                        </span>
                        </div>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <div class="{{ empty($user['rate_value']) ? '' : 'flex justify-end' }}">
                            @if($user['rate_value'])
                                {{ number_format($user['rate_value'], 0, ',', ' ') }} <strong>₽</strong>
                            @else
                                -
                            @endif
                        </div>
                    </x-data.table-cell>
                </x-data.table-row>
            @empty
                <x-data.table-row>
                    <x-data.table-cell colspan="6">
                        Нет пользователей для выбранного агентства
                    </x-data.table-cell>
                </x-data.table-row>
            @endforelse
        </x-data.table-rows>
    </x-data.table>
</div>
