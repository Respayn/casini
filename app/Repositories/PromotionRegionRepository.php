<?php

namespace App\Repositories;

use App\Data\PromotionRegionData;
use App\Models\PromotionRegion;
use App\Repositories\Interfaces\RepositoryInterface;

class PromotionRegionRepository extends EloquentRepository implements RepositoryInterface
{
    public function model()
    {
        return PromotionRegion::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return PromotionRegionData::collect($query->get());
    }

    public function find(int $id)
    {
        return  PromotionRegionData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return PromotionRegionData::from($this->model->where($column, $value)->get());
    }
}
