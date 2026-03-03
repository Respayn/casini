<?php

namespace Src\Domain\Projects;

interface ProjectRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): Project;
}