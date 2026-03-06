<?php

namespace Src\Infrastructure\Reports;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGeneratorInterface;
use Src\Domain\Reports\ReportFormat;
use Src\Infrastructure\Reports\TemplateHandlers\InlineValueHandler;
use Src\Infrastructure\Reports\TemplateHandlers\Listhandler;
use Src\Infrastructure\Reports\TemplateHandlers\TableHandler;
use Src\Infrastructure\Reports\TemplateHandlers\TemplateHandlerInterface;

class ReportGenerator implements ReportGeneratorInterface
{
    /** @var TemplateHandlerInterface[] */
    private readonly array $handlers;

    public function __construct()
    {
        $this->handlers = [
            new TableHandler(),
            new Listhandler(),
            new InlineValueHandler(),
        ];
    }

    public function generate(string $templatePath, ReportData $data, string $name, ReportFormat $format = ReportFormat::DOCX): string
    {
        $templateProcessor = new TemplateProcessor(Storage::path($templatePath));

        foreach ($this->handlers as $handler) {
            $handler->handle($templateProcessor, $data);
        }

        $directory = Storage::path('reports');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $reportFileName = $directory . '/' . $name . $format->extension();
        $templateProcessor->saveAs($reportFileName);

        return $reportFileName;
    }
}
