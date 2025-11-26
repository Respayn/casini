<?php

namespace App\Contracts;

interface ProjectPlanRepositoryInterface
{
    public function getPlansForYear(int $year): array;
    public function savePlansForYear(int $year, array $plans);
}