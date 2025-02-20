<div>
    <x-data.table>
        <x-data.table-rows>
            @foreach ($attributionModels as $attributionModel)
                <x-data.table-row>
                    <x-data.table-cell>
                        {{ $attributionModel->label() }}
                    </x-data.table-cell>
                </x-data.table-row>
            @endforeach
        </x-data.table-rows>
    </x-data.table>
    <div class="w-3/4 text-sm font-normal italic text-gray-400">
        Изменение моделей атрибуции через программиста
    </div>
</div>
