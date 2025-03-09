<?php

namespace App\Repositories;

use App\Data\ProductNotificationData;
use App\Models\Department;
use App\Repositories\Interfaces\RepositoryInterface;

class DepartmentRepository extends EloquentRepository implements RepositoryInterface
{
    public function model()
    {
        return Department::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return $query->get();
    }

    public function find(int $id)
    {
        return Department::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return Department::from($this->model->where($column, $value)->get());

    }
}
