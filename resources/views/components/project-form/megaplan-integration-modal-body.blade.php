<div
    class="flex h-full flex-col"
    x-data="{
        isActive: false,

        function save() {
            
        }
    }"
>
    <x-form.form>
        <x-form.form-field class="w-[603px]">
            <x-form.form-label>Синхронизация</x-form.form-label>
            <div>
                <x-form.toggle-switch x-model="isActive"></x-form.toggle-switch>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label required>№ корневого тикета</x-form.form-label>
            <div class="w-[305px]">
                <x-form.input-text></x-form.input-text>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label>Дополнение к строке поиска</x-form.form-label>
            <div class="w-[305px]">
                <x-form.textarea></x-form.textarea>
            </div>
        </x-form.form-field>

        <x-form.form-field>
            <x-form.form-label>Парсить работы из комментариев</x-form.form-label>
            <div>
                <x-form.toggle-switch></x-form.toggle-switch>
            </div>
        </x-form.form-field>
    </x-form.form>
    <div class="mt-auto flex justify-between">
        <x-button.button
            variant="primary"
            label="Сохранить изменения"
        />
        <x-button.button
            label="Отменить"
            x-on:click=""
        />
    </div>
</div>
