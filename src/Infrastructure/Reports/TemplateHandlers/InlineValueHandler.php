<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;

class InlineValueHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        $values = $data->getValues();

        if ($values === []) {
            return;
        }

        try {
            $templateProcessor->setValues($values);
        } catch (\Throwable $e) {
            report($e);
            $warnings->add('Часть текстовых полей шаблона не заполнена');

            foreach ($values as $key => $value) {
                try {
                    $templateProcessor->setValue($key, $value);
                } catch (\Throwable $itemError) {
                    report($itemError);
                    $warnings->add('Поле «'.$key.'» не заполнено');
                }
            }
        }
    }
}
