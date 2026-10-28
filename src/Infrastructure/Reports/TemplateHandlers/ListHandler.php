<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;
use Src\Infrastructure\Templates\ReportTemplateProcessor;

class ListHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        foreach ($data->getLists() as $key => $items) {
            try {
                $itemsCount = count($items);
                if ($itemsCount > 0) {
                    $useParagraph = $templateProcessor instanceof ReportTemplateProcessor
                        && ! $templateProcessor->isMacroInTableRow($key);

                    if ($useParagraph) {
                        $templateProcessor->cloneParagraph($key, array_map(strval(...), $items));
                    } else {
                        $templateProcessor->cloneRow($key, $itemsCount);

                        foreach ($items as $index => $item) {
                            $templateProcessor->setValue($key.'#'.($index + 1), $item);
                        }
                    }
                } else {
                    $templateProcessor->setValue($key, '');
                }
            } catch (\Throwable $e) {
                report($e);
                $warnings->add(ReportTemplateLabels::label($key).' не заполнен');
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
