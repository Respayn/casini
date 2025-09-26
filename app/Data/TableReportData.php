<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class TableReportData extends Data implements Wireable
{
    use WireableData;

    /** @var Collection<int, TableReportGroupData> */
    public Collection $groups;

    /** @var Collection<string, mixed> */
    public Collection $summary;

    public function __construct()
    {
        $this->groups = new Collection();
        $this->summary = new Collection();
    }
}
