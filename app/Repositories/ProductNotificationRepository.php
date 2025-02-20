<?php

namespace App\Repositories;

use App\Data\ProductNotificationData;
use App\Models\ProductNotification;
use App\Repositories\Interfaces\RepositoryInterface;

class ProductNotificationRepository extends EloquentRepository implements RepositoryInterface
{
    public function model()
    {
        return ProductNotification::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return ProductNotificationData::collect($query->get());
    }

    public function find(int $id)
    {
        return ProductNotificationData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return ProductNotificationData::from($this->model->where($column, $value)->get());
    }
}
