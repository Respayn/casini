<div class="flex h-full flex-col">
    <div class="mb-5">
        <x-menu.back-button />
    </div>

    <h1 class="mb-7">{{ $this->isEditing ? 'Редактирование отчета' : 'Создать отчет' }}</h1>

    <x-layout.sidebar-filter-hint class="mb-4 self-start" />

    @if ($this->noticeMessage)
        <x-feedback.notice :variant="$this->noticeVariant">
            {{ $this->noticeMessage }}
        </x-feedback.notice>
    @endif

    <form wire:submit.prevent="create" class="flex flex-1 flex-col">
        <div class="grid grid-cols-2 gap-7">
            <div>
                <span class="text-sm font-semibold">Выберите клиенто-проект *</span>
            </div>
            <div>
                <div class="max-w-80">
                    <x-form.select
                        wire:key="report-create-project-{{ $this->sidebarProjectId ?? 'all' }}-{{ count($this->projectOptions) }}"
                        wire:model.live="projectId"
                        :options="$this->projectOptions"
                        placeholder="Выберите клиенто-проект"
                        empty-placeholder="Нет доступных клиенто-проектов"
                        searchable
                        search-placeholder="Начните вводить название"
                    />
                </div>
            </div>

            <div>
                <span class="text-sm font-semibold">Выберите период отчетности *</span>
            </div>
            <div>
                <div class="flex max-w-80 justify-between gap-4">
                    <x-form.month-picker
                        wire:model.live="from"
                        placeholder="Месяц и год"
                        disable-future
                    />
                    <x-form.month-picker
                        wire:model.live="to"
                        placeholder="Месяц и год"
                        disable-future
                    />
                </div>
            </div>

            <div>
                <span class="text-sm font-semibold">Выберите формат отчетности *</span>
            </div>
            <div>
                <div class="max-w-80">
                    <x-form.select
                        wire:model.live="format"
                        :options="$this->formData->formats"
                        placeholder="Выберите формат"
                        empty-placeholder="Нет доступных форматов"
                        :disabled="count($this->formData->formats) <= 1"
                    />
                </div>
            </div>

            <div>
                <x-form.form-label
                    class="text-sm font-semibold max-w-none"
                    tooltip="Выбранный шаблон влияет на содержание отчета"
                    tooltip-panel-max-width="14rem"
                >
                    Выберите шаблон отчета *
                </x-form.form-label>
            </div>
            <div>
                <div class="max-w-80">
                    <x-form.select
                        wire:model.live="templateId"
                        :options="$this->formData->templates"
                        placeholder="Выберите шаблон"
                        empty-placeholder="Нет доступных шаблонов"
                    />
                </div>
            </div>
        </div>

        @if ($this->hasPendingChanges || $this->isEditing)
            <div class="mt-auto flex justify-between pt-7">
                <button
                    type="submit"
                    @class([
                        'inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-lg px-3.5 transition',
                        'bg-primary border-primary border text-white',
                        'hover:not-disabled:bg-transparent hover:not-disabled:text-primary',
                        'disabled:cursor-not-allowed disabled:border-0 disabled:bg-secondary disabled:text-default-button-disabled',
                    ])
                    @disabled(! $this->canSubmit)
                    wire:loading.attr="disabled"
                    wire:target="create"
                >
                    <span class="relative inline-flex size-5 shrink-0 items-center justify-center">
                        <x-icons.download
                            class="size-5"
                            wire:loading.remove
                            wire:target="create"
                        />
                        <x-icons.progress-activity
                            class="absolute inset-0 size-5 animate-spin"
                            wire:loading
                            wire:target="create"
                        />
                    </span>
                    <span class="whitespace-nowrap">Сформировать и скачать</span>
                </button>
                <x-button.button
                    type="button"
                    label="Отменить"
                    wire:click="discardChanges"
                />
            </div>
        @endif
    </form>
</div>
