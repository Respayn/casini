<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;

class ConditionHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        foreach ($data->getConditions() as $key => $render) {
            try {
                if ($render) {
                    $templateProcessor->cloneBlock($key, 1, true);
                } else {
                    $templateProcessor->deleteBlock($key);
                }
            } catch (\Throwable $e) {
                report($e);
                $warnings->add(ReportTemplateLabels::label($key).' не обработан');
            }
        }
    }
}
