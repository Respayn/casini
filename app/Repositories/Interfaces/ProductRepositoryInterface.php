<?php

namespace App\Repositories\Interfaces;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code);
}
