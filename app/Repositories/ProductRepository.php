<?php

namespace App\Repositories;

use App\Data\ProductData;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;

class ProductRepository extends EloquentRepository implements ProductRepositoryInterface
{
    public function model()
    {
        return Product::class;
    }

    public function all(array $with = [])
    {
        $query = $this->queryWith($with);
        return ProductData::collect($query->get());
    }

    public function find(int $id)
    {
        return ProductData::from($this->model->find($id));
    }

    public function findBy(string $column, mixed $value)
    {
        return ProductData::collect($this->model->where($column, $value)->get());
    }

    public function findByCode(string $code)
    {
        return ProductData::from($this->model->where('code', $code)->first());
    }
}
