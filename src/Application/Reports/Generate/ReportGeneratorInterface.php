<?php

namespace Src\Application\Reports\Generate;

use Src\Domain\Reports\ReportFormat;

interface ReportGeneratorInterface
{
    public function generate(string $templatePath, ReportData $data, string $name, ReportFormat $format = ReportFormat::DOCX): string;
}
