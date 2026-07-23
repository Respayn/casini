<div class="mt-auto flex justify-between" {{ $attributes }}>
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
