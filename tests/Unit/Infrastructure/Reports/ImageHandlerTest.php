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

    public function test_inserts_photo_at_readable_default_size(): void
    {
        $path = $this->tinyPng();

        $processor = $this->createMock(TemplateProcessor::class);
        $processor->expects($this->once())->method('getVariables')->willReturn(['manager_photo']);
        $processor->expects($this->once())
            ->method('setImageValue')
            ->with('manager_photo', [
                'path' => $path,
                'width' => ImageHandler::DEFAULT_SIZE,
                'height' => ImageHandler::DEFAULT_SIZE,
                'ratio' => true,
            ]);
        $processor->expects($this->never())->method('setValue');

        $warnings = new ReportGenerationWarnings;
        $data = ReportData::builder()->image('manager_photo', $path)->build();

        (new ImageHandler)->handle($processor, $data, $warnings);

        $this->assertSame([], $warnings->all());
        unlink($path);
    }

    public function test_keeps_size_from_template_tag(): void
    {
        $path = $this->tinyPng();

        $processor = $this->createMock(TemplateProcessor::class);
        $processor->expects($this->once())->method('getVariables')->willReturn(['manager_photo:2cm:2cm']);
        $processor->expects($this->once())->method('setImageValue')->with('manager_photo', $path);
        $processor->expects($this->never())->method('setValue');

        (new ImageHandler)->handle(
            $processor,
            ReportData::builder()->image('manager_photo', $path)->build(),
            new ReportGenerationWarnings,
        );

        unlink($path);
    }

    private function tinyPng(): string
    {
        $path = sys_get_temp_dir().'/casini-photo-'.uniqid('', true).'.png';
        file_put_contents(
            $path,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true)
        );

        return $path;
    }
}
