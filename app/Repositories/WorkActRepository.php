<?php

namespace App\Repositories;

use App\Data\Accounting\WorkActData;
use App\Models\SaoPerformedWorkAct;
use App\Repositories\Interfaces\WorkActRepositoryInterface;
use Spatie\LaravelData\DataCollection;

class WorkActRepository implements WorkActRepositoryInterface
{
    public function upsertWorkAct(WorkActData $data, ?int $projectId): \App\Models\SaoPerformedWorkAct
    {
        return SaoPerformedWorkAct::updateOrCreate(
            ['number' => $data->actNumber],
            [
                'project_id' => $projectId,
                'creation_date' => $data->actDate,
                'price' => $data->total,
                'customer_inn' => $data->company->inn,
                'contract_number' => $data->company->contractNumber,
                'customer_additional_number' => $data->company->additionalNumbers
            ]
        );
    }

    public function syncWorkActItems(SaoPerformedWorkAct $workAct, DataCollection $items): void
    {
        $workAct->items()->delete();
        foreach ($items as $item) {
            $workAct->items()->create([
                'number' => $item->number,
                'name' => $item->name,
                'quantity' => $item->count,
                'unit' => $item->unit,
                'price' => $item->price
            ]);
        }
    }
}
