<?php

namespace Tests\Unit\Infrastructure\Reports;

use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Reports\Generate\ReportData;
use Src\Application\Reports\Generate\ReportGenerationWarnings;
use Src\Infrastructure\Reports\TemplateHandlers\ImageHandler;
use Tests\TestCase;

class ImageHandlerTest extends TestCase
{
    public function test_missing_image_file_is_skipped_with_warning(): void
    {
        $processor = $this->createMock(TemplateProcessor::class);
        $processor->expects($this->once())
            ->method('setValue')
            ->with('agency_image', '');
        $processor->expects($this->never())->method('setImageValue');

        $warnings = new ReportGenerationWarnings;
        $data = ReportData::builder()
            ->image('agency_image', '/tmp/missing-agency-logo-'.uniqid().'.png')
            ->build();

        (new ImageHandler)->handle($processor, $data, $warnings);

        $this->assertSame(
            ['Логотип агентства не вставлен: файл не найден'],
            $warnings->all()
        );
    }

    public function test_empty_image_path_clears_placeholder_without_warning(): void
    {
        $processor = $this->createMock(TemplateProcessor::class);
        $processor->expects($this->once())
            ->method('setValue')
            ->with('manager_photo', '');
        $processor->expects($this->never())->method('setImageValue');

        $warnings = new ReportGenerationWarnings;
        $data = ReportData::builder()->image('manager_photo', '')->build();

        (new ImageHandler)->handle($processor, $data, $warnings);

        $this->assertSame([], $warnings->all());
    }
}
