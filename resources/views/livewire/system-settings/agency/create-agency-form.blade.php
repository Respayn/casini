<x-form.form wire:submit.prevent="submit" :is-normalized="true" class="flex flex-col gap-4 w-[700px]">
    <x-form.form-field>
        <x-form.form-label required>Название агентства</x-form.form-label>
        <x-form.input-text wire:model="form.name" placeholder="Название агентства" />
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label required>Основной часовой пояс</x-form.form-label>
        <div>
            <x-form.select
                :options="\App\Dictionaries\TimeZoneDictionary::optionsForSelect()"
                wire:model="form.timeZone"
            />
        </div>
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label>URL-адрес сайта</x-form.form-label>
        <x-form.input-text wire:model="form.url" placeholder="https://example.com" />
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label>Email агентства</x-form.form-label>
        <x-form.input-text wire:model="form.email" placeholder="agency@email.com" />
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label>Телефон</x-form.form-label>
        <x-form.input-text wire:model="form.phone" placeholder="+7..." />
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label>Фактический адрес</x-form.form-label>
        <x-form.input-text wire:model="form.address" />
    </x-form.form-field>
    <div class="flex justify-between mt-4">
        <x-button.button type="submit" icon="icons.check" variant="primary">
            <x-slot:label>Создать</x-slot:label>
        </x-button.button>
        <x-button.button wire:click="$dispatch('modal-hide', { name: 'agency-modal' })">
            <x-slot:label>Отменить</x-slot:label>
        </x-button.button>
    </div>
</x-form.form>
