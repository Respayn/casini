<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;

class TableHandler implements TemplateHandlerInterface
{
    public function handle(TemplateProcessor $templateProcessor, ReportData $data): void
    {
        foreach ($data->getTables() as $key => $tableRows) {
            $table = new Table(['borderSize' => 6, 'unit' => TblWidth::TWIP]);

            foreach ($tableRows as $row) {
                $table->addRow();
                foreach ($row as $cell) {
                    $table->addCell()->addText($cell);
                }
            }

            $templateProcessor->setComplexBlock($key, $table);
        }
    }
}