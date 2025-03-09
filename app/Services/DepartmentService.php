<?php

namespace App\Services;

use App\Repositories\DepartmentRepository;

class DepartmentService
{
    public function __construct(
      public DepartmentRepository $repository
    ) {
    }

    public function getDepartments()
    {
        return collect($this->repository->all());
    }
}
