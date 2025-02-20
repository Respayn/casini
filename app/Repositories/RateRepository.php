<?php

namespace App\Repositories;

use App\Data\RateData;
use App\Models\Rate;
use App\Repositories\Interfaces\RateRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class RateRepository extends EloquentRepository implements RateRepositoryInterface
{
    public function model(): string
    {
        return Rate::class;
    }
    
    /**
     * @param  array $with
     * @return Collection<int, RateData>
     */
    public function all(array $with = []): Collection|DataCollection
    {
        $query = $this->queryWith($with);
        return RateData::collect($query->get());
    }

    public function find(int $id)
    {
        return RateData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return RateData::collect($this->model->where($column, $value)->get());
    }
}
