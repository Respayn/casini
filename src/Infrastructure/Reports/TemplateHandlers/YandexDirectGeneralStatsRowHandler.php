<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;
use Src\Infrastructure\Reports\YandexDirectGeneralStats;

class YandexDirectGeneralStatsRowHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        $variables = $templateProcessor->getVariables();
        if (! in_array(YandexDirectGeneralStats::CAMPAIGN, $variables, true)) {
            return;
        }

        $rows = $data->getDirectCampaignStatRows();
        $keys = YandexDirectGeneralStats::rowKeys();

        if ($rows === []) {
            $this->clearPlaceholders($templateProcessor, $keys);

            return;
        }

        try {
            $templateProcessor->cloneRow(YandexDirectGeneralStats::CAMPAIGN, count($rows));

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 1;
                $templateProcessor->setValue(YandexDirectGeneralStats::CAMPAIGN.'#'.$rowNumber, $row['campaign']);
                $templateProcessor->setValue(YandexDirectGeneralStats::IMPRESSIONS.'#'.$rowNumber, $row['impressions']);
                $templateProcessor->setValue(YandexDirectGeneralStats::CLICKS.'#'.$rowNumber, $row['clicks']);
                $templateProcessor->setValue(YandexDirectGeneralStats::COST.'#'.$rowNumber, $row['cost']);
                $templateProcessor->setValue(YandexDirectGeneralStats::CTR.'#'.$rowNumber, $row['ctr']);
                $templateProcessor->setValue(YandexDirectGeneralStats::CPC.'#'.$rowNumber, $row['cpc']);
                $templateProcessor->setValue(YandexDirectGeneralStats::CONVERSIONS.'#'.$rowNumber, $row['conversions']);
                $templateProcessor->setValue(YandexDirectGeneralStats::CPL.'#'.$rowNumber, $row['cpl']);
            }
        } catch (\Throwable $e) {
            report($e);
            $warnings->add(ReportTemplateLabels::label(YandexDirectGeneralStats::CAMPAIGN).' не заполнены');
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
