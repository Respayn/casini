<div class="flex shrink-0 justify-between border-t border-gray-100 pt-5" {{ $attributes }}>
    <x-button.button
        variant="primary"
        label="Сохранить изменения"
        x-bind:disabled="!canSave"
        x-on:click="save"
    />
    <x-button.button
        label="Отменить"
        x-on:click="handleCancelClick"
    />
</div>
