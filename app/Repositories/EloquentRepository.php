<?php

namespace App\Repositories;

abstract class EloquentRepository 
{
    protected $model;

    abstract public function model();

    public function __construct()
    {
        $this->model = app($this->model());
    }

    public function queryWith(array $with = [])
    {
        return $this->model->with($with);
    }

    public function delete(int $id)
    {
        return $this->model->find($id)->delete();
    }

    public function save(array $attributes): int
    {
        if (isset($attributes['id'])) {
            $entry = $this->model->find($attributes['id']);
            $entry->update($attributes);
            return $entry->id;
        }

        $entry = $this->model->create($attributes);
        return $entry->id;
    }
}
