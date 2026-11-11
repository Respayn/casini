<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;

class TableHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        foreach ($data->getTables() as $key => $tableData) {
            try {
                $tableObj = new Table(['borderSize' => 6, 'unit' => TblWidth::TWIP]);

                if (count($tableData['headers']) > 0) {
                    $tableObj->addRow();
                    foreach ($tableData['headers'] as $header) {
                        $tableObj->addCell()->addText($header);
                    }
                }

                if (count($tableData['rows']) > 0) {
                    foreach ($tableData['rows'] as $row) {
                        $tableObj->addRow();
                        foreach ($row as $cell) {
                            $tableObj->addCell()->addText($cell);
                        }
                    }
                } else {
                    $tableObj->addRow();
                    $tableObj->addCell(null, ['gridSpan' => count($tableData['headers'])])
                        ->addText('Нет данных');
                }

                $templateProcessor->setComplexBlock($key, $tableObj);
            } catch (\Throwable $e) {
                report($e);
                $warnings->add(ReportTemplateLabels::label($key).' не заполнена');
                $this->clearPlaceholder($templateProcessor, $key);
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
