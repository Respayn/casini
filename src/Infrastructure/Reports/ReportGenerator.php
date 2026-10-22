<?php

namespace Src\Infrastructure\Reports;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Src\Application\Reports\Generate\GeneratedReportFile;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Application\Reports\Generate\ReportGeneratorInterface;
use Src\Domain\Reports\ReportFormat;
use Src\Infrastructure\Reports\TemplateHandlers\ConditionHandler;
use Src\Infrastructure\Reports\TemplateHandlers\ImageHandler;
use Src\Infrastructure\Reports\TemplateHandlers\InlineValueHandler;
use Src\Infrastructure\Reports\TemplateHandlers\ListHandler;
use Src\Infrastructure\Reports\TemplateHandlers\TableHandler;
use Src\Infrastructure\Reports\TemplateHandlers\TemplateHandlerInterface;
use Src\Infrastructure\Reports\TemplateHandlers\YandexSearchKeywordRowHandler;
use Src\Infrastructure\Reports\TemplateHandlers\YandexSearchLeftoverTagHandler;
use Src\Infrastructure\Templates\ReportTemplateProcessor;

class ReportGenerator implements ReportGeneratorInterface
{
    /** @var TemplateHandlerInterface[] */
    private readonly array $handlers;

    public function __construct()
    {
        $this->handlers = [
            new ConditionHandler,
            new TableHandler,
            new YandexSearchKeywordRowHandler,
            new ListHandler,
            new ImageHandler,
            new InlineValueHandler,
            new YandexSearchLeftoverTagHandler,
        ];
    }

    public function generate(
        string $templatePath,
        ReportData $data,
        string $name,
        ReportFormat $format = ReportFormat::DOCX,
    ): GeneratedReportFile {
        $warnings = new ReportGenerationWarnings;
        $warnings->merge($data->getWarnings());

        $templateProcessor = new ReportTemplateProcessor(Storage::path($templatePath));

        foreach ($this->handlers as $handler) {
            try {
                $handler->handle($templateProcessor, $data, $warnings);
            } catch (\Throwable $e) {
                report($e);
                $warnings->add('Один из блоков шаблона пропущен из-за ошибки');
            }
        }

        $directory = Storage::path('reports');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $reportFileName = $directory.'/'.$name.$format->extension();
        $templateProcessor->saveAs($reportFileName);

        return new GeneratedReportFile($reportFileName, $warnings->all());
    }
}
