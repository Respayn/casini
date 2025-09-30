<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class TableReportRowData extends Data implements Wireable
{
    use WireableData;

    /**
     * Уникальный идентификатор строки, который использует для идентификации сущности
     * на бекенде при выполнении массовых действий над строками
     * @var string
     */
    public ?string $id = null;

    /**
     * Набор данных, которые будут отображены в таблице.
     * Ключ - идентификатор столбца и название компонента
     * Значение - данные для отображения
     * @var Collection<string, mixed>
     */
    public Collection $data;

    public function __construct()
    {
        $this->data = new Collection();
    }
}
