<?php

namespace App\Repositories;

use Illuminate\Support\Str;

abstract class EloquentRepository
{
    protected $model;

    abstract public function model();

    public function __construct()
    {
        $this->model = app($this->model());
    }

    protected function queryWith(array $with = [])
    {
        return $this->model->with($with);
    }

    public function delete(int $id)
    {
        return $this->model->find($id)->delete();
    }

    public function save(array $attributes): int
    {
        $processedAttributes = [];
        foreach ($attributes as $key => $value) {
            $processedAttributes[Str::snake($key)] = $value;
        }

        if (isset($processedAttributes['id'])) {
            $entry = $this->model->find($processedAttributes['id']);
            $entry->update($processedAttributes);
            return $entry->id;
        }

        $entry = $this->model->create($processedAttributes);
        return $entry->id;
    }
}
