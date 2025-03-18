<?php

namespace App\Repositories;

use App\Data\DepartmentData;
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
        $clients = Department::with($with)->get();
        return DepartmentData::collect($clients);
    }

    public function find(int $id): ?DepartmentData
    {
        return DepartmentData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return DepartmentData::from($this->model->where($column, $value)->get());

    }
}
