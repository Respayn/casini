<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;

class YandexSearchKeywordRowHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        foreach ($data->getKeywordPositionPairs() as $pair) {
            $rows = $pair['rows'];

            if ($rows === []) {
                $this->clearPlaceholder($templateProcessor, $pair['phraseKey']);
                $this->clearPlaceholder($templateProcessor, $pair['positionKey']);

                continue;
            }

            try {
                $templateProcessor->cloneRow($pair['phraseKey'], count($rows));

                foreach ($rows as $index => $row) {
                    $rowNumber = $index + 1;
                    $templateProcessor->setValue($pair['phraseKey'].'#'.$rowNumber, $row['phrase']);
                    $templateProcessor->setValue($pair['positionKey'].'#'.$rowNumber, $row['position']);
                }
            } catch (\Throwable $e) {
                report($e);
                $warnings->add(ReportTemplateLabels::label($pair['phraseKey']).' не заполнены');
                $this->clearPlaceholder($templateProcessor, $pair['phraseKey']);
                $this->clearPlaceholder($templateProcessor, $pair['positionKey']);
            }
        }
    }

    private function clearPlaceholder(TemplateProcessor $templateProcessor, string $key): void
    {
        try {
            $templateProcessor->setValue($key, '');
        } catch (\Throwable) {
            // тег мог отсутствовать в шаблоне
        }
    }
}
