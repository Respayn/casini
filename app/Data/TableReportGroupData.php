<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class TableReportGroupData extends Data implements Wireable
{
    use WireableData;

    /**
     * Поле, по которому группируются данные
     * @var ?string
     */
    public ?string $groupLabel = null;

    /**
     * Summary of rows
     * @var Collection<string, mixed>
     */
    public Collection $rows;

    /**
     * Summary of summary
     * @var Collection<string, mixed>
     */
    public Collection $summary;

    public function __construct()
    {
        $this->rows = new Collection();
        $this->summary = new Collection();
    }
}
