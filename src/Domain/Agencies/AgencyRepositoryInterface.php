<?php

namespace Src\Domain\Agencies;

interface AgencyRepositoryInterface
{
    public function findById(int $id): Agency;
}
