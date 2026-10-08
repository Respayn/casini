<div
    x-data="{
        hasPendingChanges: false,
        successMessage: @js(session('success') ?: session('password_updated')),
        markDirty() {
            this.hasPendingChanges = true;
            this.successMessage = null;
        },
    }"
    x-on:input.capture="markDirty()"
    x-on:change.capture="markDirty()"
>
    <x-menu.back-button />

    <div
        x-show="successMessage"
        x-cloak
        class="border-primary text-primary-text mt-4 mb-4 max-w-[950px] break-words rounded-lg border bg-blue-50 p-4 text-sm"
        x-text="successMessage"
    ></div>

    <x-panel.scroll-panel
        class="mb-3 mt-4"
        style="max-height: calc(100vh - 300px);"
    >
        <div class="max-w-[950px]">
            @include('livewire.system-settings.users.user-form', [
                'saveDisabled' => ! $this->isSaveReady,
                'showInlineActions' => false,
                'isOwnProfile' => $this->isOwnProfile,
                'canEditUserAdminFields' => $this->canEditUserAdminFields,
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
                :disabled="! $this->isSaveReady"
                label="Сохранить изменения"
            />
            <x-button.button
                type="button"
                x-on:click="$wire.cancelChanges()"
                label="Отменить"
            />
        </div>
    </template>
</div>
