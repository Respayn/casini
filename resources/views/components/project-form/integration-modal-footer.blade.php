<div class="flex shrink-0 justify-between border-t border-gray-100 pt-5" {{ $attributes }}>
    <template x-if="typeof canEdit !== 'undefined' && !canEdit">
        <div
            class="relative inline-block"
            x-data="{ open: false }"
        >
            <span
                x-ref="fieldGuardTrigger"
                @mouseenter="open = true"
                @mouseleave="open = false"
            >
                <x-button.button
                    variant="primary"
                    label="Сохранить изменения"
                    disabled
                />
            </span>
            <template x-teleport="body">
                <div
                    class="z-1000 w-64 rounded-md bg-gray-700 p-2 text-sm italic text-white"
                    style="z-index: 1000"
                    x-show="open"
                    x-cloak
                    x-anchor.bottom="$refs.fieldGuardTrigger"
                >
                    Нет прав для изменения
                </div>
            </template>
        </div>
    </template>
    <template x-if="typeof canEdit === 'undefined' || canEdit">
        <x-button.button
            variant="primary"
            label="Сохранить изменения"
            x-bind:disabled="!canSave"
            x-on:click="save"
        />
    </template>
    <x-button.button
        label="Отменить"
        x-on:click="handleCancelClick"
    />
</div>
