<div>
    <div class="mb-5">
        <x-button.button href="{{ route('reports') }}" label="Назад" icon="icons.arrow-left" size="none"
            variant="link" class="text-secondary-text inline-flex max-h-[26px] items-center gap-3 text-[18px]" />
    </div>

    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Шаблоны отчетов</h1>
        <div class="flex items-center gap-2">
            @if ($this->canCreateReports)
                <x-button.button href="{{ route('reports.create') }}" label="Создать отчет" />
            @endif
            @if ($this->canEditTemplates)
                <x-button.button
                    variant="primary"
                    icon="icons.plus"
                    label="Загрузить шаблон"
                    href="{{ route('reports.templates.upload') }}"
                />
            @endif
        </div>
    </div>

    @if (empty($this->templates))
        <div class="mt-20 flex flex-col items-center gap-4">
            <span class="text-caption-text">Нет шаблонов для отображения</span>
            @if ($this->canEditTemplates)
                <div>
                    <x-button.button icon="icons.plus" label="Загрузить шаблон" variant="primary"
                        href="{{ route('reports.templates.upload') }}" />
                </div>
            @endif
        </div>
    @else
        <div class="mt-3">
            <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
                <x-data.table class="w-full">
                    <x-data.table-columns>
                        <x-data.table-column>Дата загрузки шаблона</x-data.table-column>
                        <x-data.table-column>Название шаблона</x-data.table-column>
                        <x-data.table-column>Найденные теги в шаблоне</x-data.table-column>
                        <x-data.table-column>Действия</x-data.table-column>
                    </x-data.table-columns>
                    <x-data.table-rows>
                        @foreach ($this->templates as $template)
                            <x-data.table-row>
                                <x-data.table-cell class="whitespace-nowrap">
                                    {{ $template->createdAt->format('d.m.Y') }}
                                </x-data.table-cell>
                                <x-data.table-cell>{{ $template->name }}</x-data.table-cell>
                                <x-data.table-cell>
                                    @if ($template->tags === null)
                                        <span class="text-secondary-text">Не удалось прочитать файл</span>
                                    @elseif (empty($template->tags))
                                        <span class="text-secondary-text">Теги не найдены</span>
                                    @else
                                        @php($tagCount = count($template->tags))
                                        <div
                                            class="flex flex-col items-start gap-2"
                                            x-data="{
                                                expanded: false,
                                                overflowing: {{ $tagCount > 2 ? 'true' : 'false' }},
                                            }"
                                            x-init="$nextTick(() => {
                                                const tags = $refs.tags
                                                if (!tags) {
                                                    return
                                                }
                                                const twoRows = parseFloat(getComputedStyle(document.documentElement).fontSize) * 3.5
                                                overflowing = tags.scrollHeight > twoRows + 1
                                            })"
                                        >
                                            <div
                                                class="flex flex-wrap gap-2 overflow-hidden"
                                                style="max-height: 3.5rem"
                                                x-ref="tags"
                                                x-bind:style="expanded ? 'max-height: none' : 'max-height: 3.5rem'"
                                            >
                                                @foreach ($template->tags as $tag)
                                                    @php($source = \Src\Domain\Templates\TemplateTagSource::fromTag($tag))
                                                    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-md bg-blue-50 px-2 py-1 text-xs"
                                                        title="Источник: {{ $source->label() }}">
                                                        <x-dynamic-component :component="'icons.logo.'.$source->value" class="size-4" />
                                                        &#123;&#123;{{ $tag }}&#125;&#125;
                                                    </span>
                                                @endforeach
                                            </div>
                                            <x-button.button
                                                type="button"
                                                variant="link"
                                                size="none"
                                                class="text-sm"
                                                x-show="overflowing"
                                                x-on:click="expanded = !expanded"
                                            >
                                                <span class="inline-flex items-center gap-2">
                                                    <span x-text="expanded ? 'Скрыть' : 'Показать все теги ({{ $tagCount }})'">
                                                        Показать все теги ({{ $tagCount }})
                                                    </span>
                                                    <x-icons.arrow
                                                        class="transition-transform duration-300"
                                                        x-bind:class="{ 'rotate-180': expanded }"
                                                    />
                                                </span>
                                            </x-button.button>
                                        </div>
                                    @endif
                                </x-data.table-cell>
                                <x-data.table-cell class="whitespace-nowrap">
                                    <div class="flex gap-2">
                                        <x-button.button wire:click="download({{ $template->id }})"
                                            icon="icons.download" title="Скачать" />
                                        @if ($this->canEditTemplates)
                                            <x-button.button
                                                href="{{ route('reports.templates.upload', $template->id) }}"
                                                icon="icons.edit"
                                                title="Редактировать"
                                            />
                                        @endif
                                        @if ($this->canDeleteTemplates)
                                            <x-button.button icon="icons.delete" title="Удалить"
                                                x-on:click="$dispatch('template-delete-ask', { id: {{ $template->id }} })" />
                                        @endif
                                    </div>
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.scroll-panel>
        </div>
    @endif

    @if ($this->canDeleteTemplates)
        <x-overlay.modal name="template-delete-confirm" title="Вы уверены?">
            <x-slot:body>
                <div x-data="{ id: null }"
                    x-on:template-delete-ask.window="id = $event.detail.id; $dispatch('modal-show', { name: 'template-delete-confirm' })">
                    <p class="text-primary-text">Шаблон будет удален. Готовые отчеты останутся, их можно скачать в списке
                        отчетов. Пересобрать отчет из этого шаблона уже не получится.</p>
                    <div class="mt-6 flex justify-between gap-4">
                        <x-button.button icon="icons.delete" label="Удалить" variant="primary"
                            x-on:click="$wire.delete(id); $dispatch('modal-hide', { name: 'template-delete-confirm' })" />
                        <x-button.button label="Отменить"
                            x-on:click="$dispatch('modal-hide', { name: 'template-delete-confirm' })" />
                    </div>
                </div>
            </x-slot>
        </x-overlay.modal>
    @endif
</div>
