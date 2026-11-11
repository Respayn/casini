<?php

namespace App\Repositories\Interfaces;

use App\Data\Accounting\WorkActData;
use App\Models\SaoPerformedWorkAct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

interface WorkActRepositoryInterface
{
    public function upsertWorkAct(WorkActData $data, ?int $projectId): SaoPerformedWorkAct;

    public function syncWorkActItems(SaoPerformedWorkAct $workAct, DataCollection $items): void;

    /**
     * @return Collection<int, SaoPerformedWorkAct>
     */
    public function getByCreationMonth(Carbon $month): Collection;
}
