<?php

namespace Src\Domain\Reports;

enum ReportFormat: string
{
    case DOCX = 'docx';
    case PDF = 'pdf';

    public function extension(): string
    {
        return ".{$this->value}";
    }
}
