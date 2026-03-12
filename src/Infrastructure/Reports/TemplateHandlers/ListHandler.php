<?php

namespace Src\Infrastructure\Reports\TemplateHandlers;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;

class ListHandler implements TemplateHandlerInterface
{
    public function handle(TemplateProcessor $templateProcessor, ReportData $data): void
    {
        $lists = $data->getLists();

        foreach ($lists as $key => $items) {
            $itemsCount = count($items);
            if ($itemsCount > 0) {
                $templateProcessor->cloneRow($key, count($items));

                foreach ($items as $index => $item) {
                    $templateProcessor->setValue($key . '#' . ($index + 1), $item);
                }
            } else {
                $templateProcessor->setValue($key, '');
            }
        }
    }
}
