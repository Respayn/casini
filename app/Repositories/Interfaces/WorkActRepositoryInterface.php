<?php

namespace App\Repositories\Interfaces;

use App\Data\Accounting\WorkActData;
use App\Models\SaoPerformedWorkAct;
use Spatie\LaravelData\DataCollection;

interface WorkActRepositoryInterface
{
    public function upsertWorkAct(WorkActData $data, ?int $projectId): SaoPerformedWorkAct;
    public function syncWorkActItems(SaoPerformedWorkAct $workAct, DataCollection $items): void;
}
