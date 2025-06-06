<form wire:submit.prevent="submit" class="flex flex-col gap-4">
    <x-form.form-field>
        <x-form.form-label required>Название агентства</x-form.form-label>
        <x-form.input-text wire:model="form.name" placeholder="Название агентства" />
        @error('form.name')@enderror
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label required>Основной часовой пояс</x-form.form-label>
        <x-form.select
            :options="collect(\DateTimeZone::listIdentifiers())->map(fn($z) => ['label' => $z, 'value' => $z])"
            wire:model="form.timeZone"
        />
        @error('form.timeZone')
        @enderror
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label>URL-адрес сайта</x-form.form-label>
        <x-form.input-text wire:model="form.url" placeholder="https://example.com" />
        @error('form.url')@enderror
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label>Email агентства</x-form.form-label>
        <x-form.input-text wire:model="form.email" placeholder="agency@email.com" />
        @error('form.email')@enderror
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label>Телефон</x-form.form-label>
        <x-form.input-text wire:model="form.phone" placeholder="+7..." />
        @error('form.phone')@enderror
    </x-form.form-field>
    <x-form.form-field>
        <x-form.form-label>Фактический адрес</x-form.form-label>
        <x-form.input-text wire:model="form.address" />
        @error('form.address')@enderror
    </x-form.form-field>
    <div class="flex justify-between mt-4">
        <x-button.button type="submit" icon="icons.check" variant="primary">
            <x-slot:label>Создать</x-slot:label>
        </x-button.button>
        <x-button.button wire:click="$dispatch('modal-hide', { name: 'agency-modal' })">
            <x-slot:label>Отменить</x-slot:label>
        </x-button.button>
    </div>
</form>
