<div>
    {{-- Шапка компонента --}}
    <div class="flex justify-between">
        <h1 class="mb-7">Шаблоны отчетов</h1>
        <div>
            <x-button.button variant="primary" onclick="document.getElementById('upload-input').click()"
                wire:loading.attr="disabled" wire:target="newTemplate">
                <span wire:loading.remove wire:target="newTemplate">Загрузить шаблон</span>
                <x-icons.progress-activity wire:loading wire:target="newTemplate" class="animate-spin" />
            </x-button.button>
            <input type="file" wire:model.live="newTemplate" hidden id="upload-input" accept=".docx">
        </div>
    </div>

    <div class="mt-3">
        @error('delete')
            <div class="mb-4 p-3 bg-red-100 text-red-700 border border-red-200 rounded">
                {{ $message }}
            </div>
        @enderror

        <x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
            <x-data.table>
                <x-data.table-columns>
                    <x-data.table-column>Дата</x-data.table-column>
                    <x-data.table-column>Название отчета</x-data.table-column>
                    <x-data.table-column>Действия</x-data.table-column>
                </x-data.table-columns>
                <x-data.table-rows>
                    @if (empty($this->templates))
                        <x-data.table-row>
                            <x-data.table-cell full class="text-center italic">
                                Нет созданных шаблонов
                            </x-data.table-cell>
                        </x-data.table-row>
                    @else
                        @foreach ($this->templates as $template)
                            <x-data.table-row>
                                <x-data.table-cell>{{ $template->getCreatedAt()->format('d.m.Y H:i:s') }}</x-data.table-cell>
                                <x-data.table-cell>{{ $template->getName() }}</x-data.table-cell>
                                <x-data.table-cell>
                                    <div class="flex gap-2">
                                        <x-button.button wire:click="download({{ $template->getId() }})" icon="icons.download"
                                            title="Скачать"></x-button.button>
                                        <x-button.button wire:confirm="Удалить шаблон?"
                                            wire:click="delete({{ $template->getId() }})" icon="icons.delete"
                                            title="Удалить"></x-button.button>
                                    </div>
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    @endif
                </x-data.table-rows>
            </x-data.table>
        </x-panel.scroll-panel>
    </div>
</div>