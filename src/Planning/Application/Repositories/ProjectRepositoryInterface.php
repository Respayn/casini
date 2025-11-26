<?php

namespace Src\Planning\Application\Repositories;

use Src\Planning\Domain\Project;

interface ProjectRepositoryInterface
{
    public function find(int $id): ?Project;
}