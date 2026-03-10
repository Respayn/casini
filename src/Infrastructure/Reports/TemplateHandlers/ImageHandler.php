<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;

class ImageHandler implements TemplateHandlerInterface
{
    public function handle(TemplateProcessor $templateProcessor, ReportData $data): void
    {
        $images = $data->getImages();

        foreach ($images as $key => $image) {
            if ($image === '') {
                $templateProcessor->setValue($key, '');
            } else {
                $templateProcessor->setImageValue($key, $image);
            }
        }
    }
}
