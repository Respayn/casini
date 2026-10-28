<?php

namespace Tests\Unit\Domain\Reports;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Domain\Projects\Project;
use Src\Domain\Reports\Report;
use Src\Domain\Reports\ReportFormat;
use Src\Domain\Reports\ReportNamingService;
use Src\Domain\ValueObjects\DateTimeRange;
use Src\Domain\ValueObjects\ProjectType;

class ReportNamingServiceTest extends TestCase
{
    public function test_name_contains_type_domain_and_text_period(): void
    {
        $name = (new ReportNamingService)->generateName(
            $this->report(new DateTimeRange(
                new DateTimeImmutable('2026-09-01'),
                new DateTimeImmutable('2026-09-30'),
            )),
            $this->project(ProjectType::SEO_PROMOTION, 'mmk-metiz.ru'),
        );

        $this->assertSame('Отчет SEO-продвижение mmk-metiz.ru сентябрь 2026', $name);
    }

    public function test_month_range_stays_in_text(): void
    {
        $name = (new ReportNamingService)->generateName(
            $this->report(
                new DateTimeRange(
                    new DateTimeImmutable('2026-09-01'),
                    new DateTimeImmutable('2026-10-31'),
                ),
                ProjectType::CONTEXT_AD,
            ),
            $this->project(ProjectType::CONTEXT_AD, 'example.ru'),
        );

        $this->assertSame('Отчет Контекстная реклама example.ru сентябрь 2026 - октябрь 2026', $name);
    }

    public function test_unsafe_characters_are_removed_from_filename(): void
    {
        $name = (new ReportNamingService)->generateName(
            $this->report(new DateTimeRange(
                new DateTimeImmutable('2026-09-01'),
                new DateTimeImmutable('2026-09-30'),
            )),
            $this->project(ProjectType::SEO_PROMOTION, 'mmk-metiz.ru/promo'),
        );

        $this->assertSame('Отчет SEO-продвижение mmk-metiz.ru promo сентябрь 2026', $name);
        $this->assertStringNotContainsString('/', $name);
    }

    private function project(ProjectType $type, string $domain): Project
    {
        return new Project(30, $domain, 1, $type, 1, $domain);
    }

    private function report(DateTimeRange $period, ProjectType $type = ProjectType::SEO_PROMOTION): Report
    {
        return Report::create(
            97,
            'Шаблон',
            1,
            $type,
            30,
            $period,
            1,
            1,
            ReportFormat::DOCX,
            false,
            false,
            false,
            1,
        );
    }
}
