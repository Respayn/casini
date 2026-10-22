<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Infrastructure\Reports\YandexSearchIndexedTags;

class YandexSearchLeftoverTagHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        foreach ($templateProcessor->getVariables() as $variable) {
            if (! YandexSearchIndexedTags::isSearchApiTag($variable)) {
                continue;
            }

            try {
                $templateProcessor->setValue($variable, '');
            } catch (\Throwable) {
                // тег мог отсутствовать в шаблоне
            }
        }
    }
}
