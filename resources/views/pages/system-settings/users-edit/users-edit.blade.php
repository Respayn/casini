<div>
    <x-menu.back-button />
    <div class="max-w-[950px]" class="mt-4">
        @include('livewire.system-settings.users.user-form', [
            'saveDisabled' => ! $this->isSaveReady,
            'isOwnProfile' => $this->isOwnProfile,
            'canEditUserAdminFields' => $this->canEditUserAdminFields,
        ])
    </div>
</div>
