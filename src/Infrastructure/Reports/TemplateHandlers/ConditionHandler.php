<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;

class ConditionHandler implements TemplateHandlerInterface
{
    public function handle(TemplateProcessor $templateProcessor, ReportData $data): void
    {
        $conditions = $data->getConditions();

        foreach ($conditions as $key => $render) {
            if ($render) {
                $templateProcessor->cloneBlock($key, 1, true);
            } else {
                $templateProcessor->deleteBlock($key);
            }
        }
    }
}