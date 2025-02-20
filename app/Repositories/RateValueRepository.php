<?php

namespace App\Repositories;

use App\Data\RateValueData;
use App\Models\RateValue;
use App\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class RateValueRepository extends EloquentRepository implements RepositoryInterface
{
    public function model()
    {
        return RateValue::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return RateValueData::collect($query->get());
    }

    public function find(int $id)
    {
        return RateValueData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value): Collection|DataCollection
    {
        return RateValueData::collect($this->model->where($column, $value)->get());
    }

    public function findLast(int $rateId)
    {
        return RateValueData::from($this->model->where('rate_id', $rateId)->latest('start_date')->first());
    }
}
