<?php

namespace App\Repositories;

use App\Data\IntegrationData;
use App\Models\Integration;
use App\Repositories\Interfaces\IntegrationRepositoryInterface;

class IntegrationRepository extends EloquentRepository implements IntegrationRepositoryInterface
{
    public function model()
    {
        return Integration::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return IntegrationData::collect($query->get());
    }

    public function find(int $id)
    {
        return IntegrationData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return IntegrationData::collect($this->model->where($column, $value)->get());
    }
}
