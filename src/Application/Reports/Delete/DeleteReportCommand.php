<?php

namespace Src\Application\Reports\Delete;

class DeleteReportCommand
{
    public function __construct(public int $reportId) {}
}
