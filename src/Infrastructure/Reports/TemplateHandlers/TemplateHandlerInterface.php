<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;

interface TemplateHandlerInterface
{
    public function handle(TemplateProcessor $templateProcessor, ReportData $data): void;
}
