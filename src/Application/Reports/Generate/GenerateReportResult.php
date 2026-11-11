<?php

namespace Src\Application\Reports\Generate;

class GenerateReportResult
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly int $reportId,
        public readonly array $warnings = [],
    ) {}
}
