<div class="flex flex-col h-full">
    <div class="mb-5">
        <x-menu.back-button />
    </div>

    <h1 class="mb-9">Создать отчет</h1>

    <div class="grid grid-cols-2 gap-7">
        <div>
            <span class="font-semibold text-sm">Выберите клиенто-проект *</span>
        </div>
        <div>
            <div class="max-w-80">
                <x-form.select wire:model="projectId" :options="$this->formData->projects" />
            </div>
        </div>

        <div>
            <span class="font-semibold text-sm">Выберите период отчетности *</span>
        </div>
        <div>
            <div class="flex justify-between gap-9 max-w-80">
                <x-form.month-picker wire:model="from" borderColor="#94A8C1" />
                <x-form.month-picker wire:model="to" borderColor="#94A8C1" />
            </div>
        </div>

        <div>
            <span class="font-semibold text-sm">Выберите формат отчетности *</span>
        </div>
        <div>
            <div class="max-w-80">
                <x-form.select wire:model="format" :options="$this->formData->formats" />
            </div>
        </div>

        <div class="flex flex-col">
            <span class="font-semibold text-sm">Выберите шаблон отчета *</span>
            <span class="italic text-caption-text">Выбранный шаблон влияет на содержание отчета</span>
        </div>
        <div>
            <div class="max-w-80">
                <x-form.select wire:model="templateId" :options="$this->formData->templates" />
            </div>
        </div>
    </div>

    <div class="flex justify-between mt-auto">
        <div class="flex gap-2"">
            <x-button.button wire:click="create" variant="primary" label="Сформировать" />
            <x-button.button wire:click="create(true)" variant="primary" label="Сформировать и скачать"
                icon="icons.download" />
        </div>
        <x-button.button href="{{ route('reports') }}" label="Отменить" />
    </div>
</div>