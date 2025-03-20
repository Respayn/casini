<?php

namespace App\Repositories\Interfaces;

interface ProjectUtmMappingRepositoryInterface extends RepositoryInterface
{
    public function saveProjectUtmMappings(array $data, int $projectId): void;
}
