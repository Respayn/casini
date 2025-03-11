<?php

namespace App\Services;

use App\Data\DepartmentData;
use App\Repositories\DepartmentRepository;
use Illuminate\Support\Collection;

class DepartmentService
{
    public function __construct(
      public DepartmentRepository $repository
    ) {
    }

    /**
     * @return Collection<DepartmentData>
     */
    public function getDepartments(): Collection
    {
        return collect($this->repository->all());
    }
}
