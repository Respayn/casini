<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;

class ImageHandler implements TemplateHandlerInterface
{
    public function handle(
        TemplateProcessor $templateProcessor,
        ReportData $data,
        ReportGenerationWarnings $warnings,
    ): void {
        foreach ($data->getImages() as $key => $image) {
            if ($image === '') {
                $this->clearPlaceholder($templateProcessor, $key);

                continue;
            }

            if (! is_file($image)) {
                $warnings->add(ReportTemplateLabels::label($key).' не вставлен: файл не найден');
                $this->clearPlaceholder($templateProcessor, $key);

                continue;
            }

            try {
                $templateProcessor->setImageValue($key, $image);
            } catch (\Throwable $e) {
                report($e);
                $warnings->add(ReportTemplateLabels::label($key).' не вставлен: файл повреждён или не подходит');
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
