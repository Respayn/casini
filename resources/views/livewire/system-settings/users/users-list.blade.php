<div class="flex flex-col gap-3">
    <div class="flex justify-between items-center">
        <h1 class="text-primary-text text-xl font-semibold">Пользователи и роли</h1>
        <div class="flex gap-2 items-center">
            <x-button.button
                label="+ Добавить пользователя"
                variant="primary"
                wire:click="openCreateUserModal"
            />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <label class="inline-flex items-center">
            <input type="checkbox" wire:model="onlyActive" class="form-checkbox" />
            <span class="ml-2">Показывать только активные</span>
        </label>
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
                        <a href="#" class="link" wire:click.prevent="editUser({{ $user['id'] }})">
                            {{ $user['login'] }}
                        </a>
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ $user['last_name'] }} {{ $user['first_name'] }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ is_array($user['roles']) ? implode(', ', $user['roles']) : $user['roles'] }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ $user['is_active'] ? 'Активный' : 'Неактивный' }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        {{ $user['rate_value'] ?? '-' }}
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
