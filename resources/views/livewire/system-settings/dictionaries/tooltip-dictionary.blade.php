<div>
    <x-data.table>
        <x-data.table-columns>
            <x-data.table-column>Путь</x-data.table-column>
            <x-data.table-column>Поле рядом с тултипом</x-data.table-column>
            <x-data.table-column>Содержание тултипа</x-data.table-column>
        </x-data.table-columns>

        <x-data.table-rows>
            @foreach ($tooltips as $index => $tooltip)
                <x-data.table-row wire:key="tooltip-{{ $tooltip->code }}">
                    <x-data.table-cell class="bg-slate-100">
                        {{ $tooltip->path }}
                    </x-data.table-cell>
                    <x-data.table-cell class="bg-slate-100">
                        {{ $tooltip->label }}
                    </x-data.table-cell>
                    <x-data.table-cell>
                        <div
                            class="flex items-center justify-between gap-1"
                            x-data="{
                                isEditing: false,
                                updateTooltip(code) {
                                    $wire.tooltips[{{ $index }}].content = $refs.textarea.value;
                                    $wire.updateTooltip(code, $refs.textarea.value);
                                    this.isEditing = false;
                                }
                            }"
                        >
                            <div>
                                <div x-show="!isEditing">
                                    {!! nl2br($tooltips[$index]->content) !!}
                                </div>
                                <x-form.textarea
                                    x-ref="textarea"
                                    x-show="isEditing"
                                    x-cloak
                                    wire:model="tooltips.{{ $index }}.content"
                                >
                                </x-form.textarea>
                            </div>
                            <div>
                                <x-button.button
                                    variant="ghost"
                                    icon="icons.edit"
                                    x-on:click="isEditing = true; $focus.focus($refs.textarea)"
                                    x-show="!isEditing"
                                />
                                <x-button.button
                                    variant="ghost"
                                    icon="icons.check"
                                    x-on:click="updateTooltip('{{ $tooltip->code }}')"
                                    x-show="isEditing"
                                />
                                <x-button.button
                                    variant="ghost"
                                    icon="icons.close"
                                    x-on:click="isEditing = false; $wire.tooltips[{{ $index }}].content = `{{ $tooltip->content }}`"
                                    x-show="isEditing"
                                />
                            </div>
                        </div>
                    </x-data.table-cell>
                </x-data.table-row>
            @endforeach
        </x-data.table-rows>
    </x-data.table>


</div>
