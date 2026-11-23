<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportTemplateLabels;

class ImageHandler implements TemplateHandlerInterface
{
    /**
     * PhpWord без размера ставит 115x70 px: фото менеджера сжимается в значок.
     * Если в теге уже есть размер ({{manager_photo:2cm:2cm}}), оставляем его.
     */
    public const DEFAULT_SIZE = '3.5cm';

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
                $templateProcessor->setImageValue($key, $this->imageValue($templateProcessor, $key, $image));
            } catch (\Throwable $e) {
                report($e);
                $warnings->add(ReportTemplateLabels::label($key).' не вставлен: файл повреждён или не подходит');
                $this->clearPlaceholder($templateProcessor, $key);
            }
        }
    }

    /**
     * @return string|array{path: string, width?: string, height?: string, ratio?: bool}
     */
    private function imageValue(TemplateProcessor $templateProcessor, string $key, string $image): array|string
    {
        foreach ($templateProcessor->getVariables() as $variable) {
            if ($variable !== $key && str_starts_with($variable, $key.':')) {
                return $image;
            }
        }

        return [
            'path' => $image,
            'width' => self::DEFAULT_SIZE,
            'height' => self::DEFAULT_SIZE,
            'ratio' => true,
        ];
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
