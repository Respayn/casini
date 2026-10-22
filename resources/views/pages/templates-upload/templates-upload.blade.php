<div class="flex h-full min-h-0 flex-col">
    <div class="min-h-0 flex-1 overflow-y-auto">
        <div class="mb-5">
            <x-button.button
                href="{{ route('reports.templates') }}"
                label="Назад"
                icon="icons.arrow-left"
                size="none"
                variant="link"
                class="text-secondary-text inline-flex max-h-[26px] items-center gap-3 text-[18px]"
            />
        </div>

        <h1 class="mb-7">{{ $this->isEditing ? 'Редактирование шаблона' : 'Создание шаблона' }}</h1>

        <p class="mb-7 text-sm">
            <span @class([
                'font-semibold',
                'text-primary-text' => $step === 1,
                'text-secondary-text' => $step !== 1,
            ])>1. Создайте шаблон</span>
            <span class="text-secondary-text"> / </span>
            <span @class([
                'font-semibold',
                'text-primary-text' => $step === 2,
                'text-secondary-text' => $step !== 2,
            ])>2. Загрузите шаблон</span>
        </p>

        @if ($step === 1)
            <ol class="text-primary-text list-decimal space-y-2 pl-5 text-sm">
                <li>Откройте файл Word.</li>
                <li>Откройте список тегов.</li>
                <li>Вставьте тег туда, где должно появиться значение. Формат: @{{название}}.</li>
                <li>Сохраните файл как .docx.</li>
                <li>На следующем шаге загрузите его.</li>
            </ol>

            <div
                class="mt-7"
                x-data="{ open: @entangle('showTags').live }"
            >
                <x-button.button
                    type="button"
                    x-on:click="open = !open"
                >
                    <span class="inline-flex items-center gap-2">
                        Посмотреть список тегов
                        <x-icons.arrow
                            class="transition-transform duration-300"
                            x-bind:class="{ 'rotate-180': open }"
                        />
                    </span>
                </x-button.button>
            </div>

            @if ($showTags)
                <div class="mt-7">
                    <p class="text-primary-text mb-4 text-sm">
                        Скопируйте тег и вставьте его в Word. При создании отчета вместо тега появятся данные проекта за выбранный период.
                    </p>

                    <x-panel.scroll-panel style="max-height: min(20rem, calc(100vh - 22rem))">
                        <x-data.table class="w-full">
                            <x-data.table-columns>
                                <x-data.table-column>Тег</x-data.table-column>
                                <x-data.table-column>Что подставляет</x-data.table-column>
                                <x-data.table-column>Формат отображения</x-data.table-column>
                            </x-data.table-columns>
                            <x-data.table-rows>
                                @foreach ($this->allTags as $tag)
                                    <x-data.table-row>
                                        <x-data.table-cell>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span
                                                    class="inline-flex shrink-0"
                                                    title="Источник: {{ $tag->source()->label() }}"
                                                >
                                                    <x-dynamic-component
                                                        :component="'icons.logo.'.$tag->source()->value"
                                                        class="size-4"
                                                    />
                                                </span>
                                                <span>{{ $tag->placeholder() }}</span>
                                                <x-button.button
                                                    type="button"
                                                    variant="link"
                                                    size="xs"
                                                    icon="icons.content-copy"
                                                    title="Скопировать"
                                                    aria-label="Скопировать"
                                                    class="hover:no-underline focus:outline-none"
                                                    data-copy="{{ $tag->placeholder() }}"
                                                    x-on:click="navigator.clipboard.writeText($event.currentTarget.dataset.copy)"
                                                />
                                            </div>
                                        </x-data.table-cell>
                                        <x-data.table-cell>{{ $tag->label }}</x-data.table-cell>
                                        <x-data.table-cell>{{ $tag->parameterLabel() }}</x-data.table-cell>
                                    </x-data.table-row>
                                @endforeach
                            </x-data.table-rows>
                        </x-data.table>
                    </x-panel.scroll-panel>
                </div>
            @endif
        @endif

        @if ($step === 2)
            <div class="grid grid-cols-2 gap-7">
                <div>
                    <x-form.form-label
                        class="text-sm font-semibold max-w-none"
                        required
                    >
                        Название шаблона
                    </x-form.form-label>
                </div>
                <div>
                    <div class="max-w-80">
                        <x-form.input-text
                            wire:model.live.debounce.300="templateName"
                            placeholder="Название шаблона"
                        />
                    </div>
                </div>

                <div>
                    <x-form.form-label
                        class="text-sm font-semibold max-w-none"
                        required
                        tooltip="Можно загружать только файлы Word в формате .docx"
                    >
                        Файл шаблона
                    </x-form.form-label>
                </div>
                <div>
                    <div
                        class="flex max-w-80 flex-col items-start gap-1"
                        x-data
                    >
                        <input
                            x-ref="templateInput"
                            type="file"
                            accept=".docx"
                            class="sr-only"
                            wire:model="newTemplate"
                        />

                        @if ($this->selectedFileName)
                            <div class="flex w-full items-center gap-2">
                                <button
                                    type="button"
                                    class="border-input-border text-primary-text min-h-[42px] min-w-0 flex-1 truncate rounded-[5px] border px-3 text-left text-sm"
                                    title="{{ $this->selectedFileName }}"
                                    x-on:click="$refs.templateInput.click()"
                                >
                                    {{ $this->selectedFileName }}
                                </button>
                                <x-button.button
                                    type="button"
                                    wire:click="deleteTemplateFile"
                                    icon="icons.delete"
                                    title="Удалить файл шаблона"
                                    aria-label="Удалить файл шаблона"
                                />
                            </div>
                        @else
                            <x-button.button
                                type="button"
                                label="Выбрать файл"
                                x-on:click="$refs.templateInput.click()"
                                wire:loading.attr="disabled"
                                wire:target="newTemplate"
                            />
                            <span
                                class="text-secondary-text text-sm"
                                wire:loading
                                wire:target="newTemplate"
                            >
                                Загрузка файла
                            </span>
                        @endif

                        @error('newTemplate')
                            <span class="text-warning-red text-[12px]">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="relative z-10 mt-auto shrink-0 bg-white">
        <div class="mt-7 flex justify-between">
            @if ($step === 1)
                <x-button.button
                    type="button"
                    variant="primary"
                    label="Следующий шаг"
                    wire:click="goToStepTwo"
                />
            @else
                <div class="flex gap-3">
                    <x-button.button
                        type="button"
                        variant="primary"
                        label="Сохранить шаблон"
                        wire:click="saveTemplate"
                        :disabled="! $this->canSaveTemplate"
                    />
                    <x-button.button
                        type="button"
                        label="Назад"
                        wire:click="$set('step', 1)"
                    />
                </div>
            @endif
            <x-button.button
                href="{{ route('reports.templates') }}"
                label="Отменить"
            />
        </div>
    </div>
</div>
