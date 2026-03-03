<div>
    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Отчеты</h1>
        <div>
            <x-button.button href="{{ route('reports.create') }}" icon="icons.plus" label="Создать отчет" variant="primary" />
            <x-button.button href="{{ route('templates') }}" label="Шаблоны отчетов" variant="primary" />
        </div>
    </div>

    {{-- Фильтры --}}
    <div class="flex items-center">
        <div class="mr-3.5">
            <label>Неактивные клиенто-проекты:</label>
            <x-form.checkbox />
        </div>

        <div class="flex gap-2">
            <x-form.month-picker />
            <x-form.month-picker />
        </div>

        <div class="flex-end ml-auto">
            <x-overlay.modal-trigger name="column-settings-modal">
                <x-button.button icon="icons.edit" label="Настроить столбцы" variant="link" />
            </x-overlay.modal-trigger>
        </div>
    </div>

    <div class="mt-20 flex flex-col items-center gap-4">
        <span class="text-caption-text">Нет отчетов для отображения</span>
        <div>
            <x-button.button href="{{ route('system-settings.clients-and-projects') }}" target="_blank"
                icon="icons.plus" label="Добавить клиенто-проект" variant="primary" />
        </div>
    </div>
</div>