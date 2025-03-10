<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Interfaces\RepositoryInterface;

class ClientRepository extends EloquentRepository implements RepositoryInterface
{
    public function model()
    {
        return Client::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return $query->get();
    }

    public function find(int $id)
    {
        return Client::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return Client::from($this->model->where($column, $value)->get());

    }
}
