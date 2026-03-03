<?php

namespace Src\Domain\Reports;

interface ReportRepositoryInterface
{
    public function findById(int $id): Report;
    public function save(Report $report): int;
}