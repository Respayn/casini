<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Infrastructure\Reports\KeywordPairThreshold;
use Src\Infrastructure\Templates\ReportTemplateProcessor;

class PeriodColumnExpanderHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        if (! $data->shouldExpandPeriodColumns() || ! $templateProcessor instanceof ReportTemplateProcessor) {
            return;
        }

        $labels = $data->getPeriodColumnLabels();

        foreach ($data->getPeriodSeries() as $tag => $values) {
            if (! $templateProcessor->isMacroInTableRow($tag)) {
                continue;
            }

            try {
                $templateProcessor->expandTableCellColumns($tag, $labels, $values);
            } catch (\Throwable $e) {
                report($e);
                $warnings->add('Колонки периода для «'.$tag.'» не разложены');
            }
        }

        foreach ($data->getKeywordPositionPairs() as $pair) {
            $variables = array_map(
                static fn (string $variable): string => preg_replace('/#\d+$/', '', $variable) ?? $variable,
                $templateProcessor->getVariables(),
            );
            $resolved = KeywordPairThreshold::resolve(
                $pair['phraseKey'],
                $pair['positionKey'],
                $variables,
            );
            $rows = KeywordPairThreshold::filterRows($pair['rows'], $resolved['min']);

            foreach ($rows as $index => $row) {
                $series = $row['series'] ?? null;
                if (! is_array($series) || $series === []) {
                    continue;
                }

                $key = $resolved['positionKey'].'#'.($index + 1);
                if (! $templateProcessor->isMacroInTableRow($key)) {
                    continue;
                }

                try {
                    $templateProcessor->expandTableCellColumns($key, $labels, $series);
                } catch (\Throwable $e) {
                    report($e);
                    $warnings->add('Колонки периода для «'.$pair['positionKey'].'» не разложены');
                }
            }
        }
    }
}
