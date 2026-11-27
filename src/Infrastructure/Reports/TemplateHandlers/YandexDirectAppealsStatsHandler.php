<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;
use Src\Infrastructure\Reports\YandexDirectAppealsStats;
use Src\Infrastructure\Templates\ReportTemplateProcessor;

class YandexDirectAppealsStatsHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        $variables = $templateProcessor->getVariables();
        $hasAny = false;
        foreach (YandexDirectAppealsStats::placeholderKeys() as $key) {
            if (in_array($key, $variables, true)) {
                $hasAny = true;
                break;
            }
        }
        if (! $hasAny) {
            return;
        }

        $stats = $data->getDirectAppealsStats();
        $keys = YandexDirectAppealsStats::placeholderKeys();

        if ($stats === null
            || $stats['campaign_names'] === []
            || $stats['rows'] === []
            || ! $templateProcessor instanceof ReportTemplateProcessor
        ) {
            $this->clearPlaceholders($templateProcessor, $keys);

            return;
        }

        try {
            $templateProcessor->cloneRow(YandexDirectAppealsStats::GOAL, count($stats['rows']));

            foreach ($stats['rows'] as $index => $row) {
                $rowNumber = $index + 1;
                $templateProcessor->setValue(YandexDirectAppealsStats::GOAL.'#'.$rowNumber, $row['goal']);
                $templateProcessor->expandTableCellColumns(
                    YandexDirectAppealsStats::VALUE.'#'.$rowNumber,
                    $stats['campaign_names'],
                    $row['values'],
                );
            }

            $templateProcessor->expandTableCellColumns(
                YandexDirectAppealsStats::TOTAL,
                $stats['campaign_names'],
                $stats['totals'],
            );

            $this->clearPlaceholders($templateProcessor, [YandexDirectAppealsStats::CAMPAIGN]);
        } catch (\Throwable $e) {
            report($e);
            $warnings->add(ReportTemplateLabels::label(YandexDirectAppealsStats::GOAL).' не заполнены');
            $this->clearPlaceholders($templateProcessor, $keys);
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function clearPlaceholders(TemplateProcessor $templateProcessor, array $keys): void
    {
        foreach ($keys as $key) {
            try {
                $templateProcessor->setValue($key, '');
            } catch (\Throwable) {
                // тег мог отсутствовать в шаблоне
            }
        }
    }
}
