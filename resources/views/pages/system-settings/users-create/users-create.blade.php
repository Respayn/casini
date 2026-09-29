<div
    x-data="{
        hasPendingChanges: false,
        markDirty() {
            this.hasPendingChanges = true;
        },
    }"
    x-on:input.capture="markDirty()"
    x-on:change.capture="markDirty()"
>
    <x-menu.back-button />

    <x-panel.scroll-panel
        class="mb-3 mt-4"
        style="max-height: calc(100vh - 300px);"
    >
        <div class="max-w-[950px]">
            @include('livewire.system-settings.users.user-form', [
                'showInlineActions' => false,
                'canEditUserAdminFields' => true,
            ])
        </div>
    </x-panel.scroll-panel>

    <template x-if="hasPendingChanges">
        <div class="flex max-w-[950px] justify-between">
            <x-button.button
                type="button"
                variant="primary"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
                label="Создать пользователя"
            />
            <x-button.button
                type="button"
                x-on:click="$wire.cancelChanges()"
                label="Отменить"
            />
        </div>
    </template>
</div>
