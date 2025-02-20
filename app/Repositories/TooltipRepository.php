<?php

namespace App\Repositories;

use App\Data\TooltipData;
use App\Models\Tooltip;
use App\Repositories\Interfaces\TooltipRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class TooltipRepository extends EloquentRepository implements TooltipRepositoryInterface
{
    public function model()
    {
        return Tooltip::class;
    }

    public function all(array $with = []): Collection|DataCollection
    {
        $query = $this->queryWith($with);
        return TooltipData::collect($query->get());
    }

    public function find(int $id)
    {
        return TooltipData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return TooltipData::collect($this->model->where($column, $value)->get());
    }

    public function findByCode(string $code): TooltipData
    {
        return TooltipData::from($this->model->where('code', $code)->first());
    }
}
