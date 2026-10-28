<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;
use Src\Infrastructure\Reports\KeywordPairThreshold;

class YandexSearchKeywordRowHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        foreach ($data->getKeywordPositionPairs() as $pair) {
            $resolved = KeywordPairThreshold::resolve(
                $pair['phraseKey'],
                $pair['positionKey'],
                $templateProcessor->getVariables(),
            );
            $rows = KeywordPairThreshold::filterRows($pair['rows'], $resolved['min']);
            $keysToClear = array_values(array_unique([
                $pair['phraseKey'],
                $pair['positionKey'],
                $resolved['phraseKey'],
                $resolved['positionKey'],
            ]));

            if ($rows === []) {
                $this->clearPlaceholders($templateProcessor, $keysToClear);

                continue;
            }

            try {
                $templateProcessor->cloneRow($resolved['phraseKey'], count($rows));

                foreach ($rows as $index => $row) {
                    $rowNumber = $index + 1;
                    $templateProcessor->setValue($resolved['phraseKey'].'#'.$rowNumber, $row['phrase']);
                    $templateProcessor->setValue($resolved['positionKey'].'#'.$rowNumber, $row['position']);
                }
            } catch (\Throwable $e) {
                report($e);
                $warnings->add(ReportTemplateLabels::label($pair['phraseKey']).' не заполнены');
                $this->clearPlaceholders($templateProcessor, $keysToClear);
            }
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function clearPlaceholders(TemplateProcessor $templateProcessor, array $keys): void
    {
        foreach ($keys as $key) {
            $this->clearPlaceholder($templateProcessor, $key);
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
