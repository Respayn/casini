<?php

namespace App\Repositories\Interfaces;

interface RepositoryInterface
{
    public function all(array $with = []);
    public function find(int $id);
    public function findBy(string $column, mixed $value);
    public function save(array $attributes): int;
    public function delete(int $id);
}
