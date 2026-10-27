<?php

namespace Tests\Unit\Domain\Reports;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Domain\Reports\Report;
use Src\Domain\Reports\ReportFormat;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\ValueObjects\ProjectType;

class ReportReplaceTest extends TestCase
{
    public function test_replace_updates_fields_and_keeps_identity(): void
    {
        $createdAt = new DateTimeImmutable('2026-09-01 10:00:00');
        $report = Report::restore(
            18,
            $createdAt,
            1,
            'Старый шаблон',
            2,
            ProjectType::SEO_PROMOTION,
            3,
            new DateTimeRange(
                new DateTimeImmutable('2026-08-01'),
                new DateTimeImmutable('2026-08-31'),
            ),
            4,
            5,
            ReportFormat::DOCX,
            false,
            false,
            false,
            6,
            '/tmp/old.docx',
        );

        $report->replace(
            11,
            'Новый шаблон',
            12,
            ProjectType::CONTEXT_AD,
            13,
            new DateTimeRange(
                new DateTimeImmutable('2026-09-01'),
                new DateTimeImmutable('2026-09-30'),
            ),
            14,
            15,
            ReportFormat::DOCX,
        );

        $this->assertTrue($report->isPersisted());
        $this->assertSame(18, $report->getId());
        $this->assertEquals($createdAt, $report->getCreatedAt());
        $this->assertSame(6, $report->getCreatedBy());
        $this->assertSame(11, $report->getTemplateId());
        $this->assertSame('Новый шаблон', $report->getTemplateName());
        $this->assertSame(12, $report->getClientId());
        $this->assertSame(ProjectType::CONTEXT_AD, $report->getProjectType());
        $this->assertSame(13, $report->getProjectId());
        $this->assertSame('2026-09-01', $report->getPeriodStart()?->format('Y-m-d'));
        $this->assertSame(14, $report->getSpecialistId());
        $this->assertSame(15, $report->getManagerId());
        $this->assertSame('/tmp/old.docx', $report->getPath());
    }

    public function test_restore_keeps_template_name_without_template_id(): void
    {
        $report = Report::restore(
            19,
            new DateTimeImmutable('2026-09-01 10:00:00'),
            null,
            'Снимок шаблона',
            2,
            ProjectType::SEO_PROMOTION,
            3,
            new DateTimeRange(
                new DateTimeImmutable('2026-08-01'),
                new DateTimeImmutable('2026-08-31'),
            ),
            4,
            5,
            ReportFormat::DOCX,
            true,
            false,
            false,
            6,
            '/tmp/ready.docx',
        );

        $this->assertNull($report->getTemplateId());
        $this->assertSame('Снимок шаблона', $report->getTemplateName());
    }
}
