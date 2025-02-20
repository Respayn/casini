<?php

namespace App\Repositories;

use App\Data\PromotionTopicData;
use App\Models\PromotionTopic;
use App\Repositories\Interfaces\RepositoryInterface;

class PromotionTopicRepository extends EloquentRepository implements RepositoryInterface
{
    public function model()
    {
        return PromotionTopic::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return PromotionTopicData::collect($query->get());
    }

    public function find(int $id)
    {
        return PromotionTopicData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return PromotionTopicData::collect($this->model->where($column, $value)->get());
    }
}
