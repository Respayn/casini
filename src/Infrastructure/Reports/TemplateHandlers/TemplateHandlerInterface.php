<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;

interface TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void;
}
