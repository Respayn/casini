<?php

namespace Src\Application\Reports\Generate;

class GeneratedReportFile
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly string $path,
        public readonly array $warnings = [],
    ) {}
}
