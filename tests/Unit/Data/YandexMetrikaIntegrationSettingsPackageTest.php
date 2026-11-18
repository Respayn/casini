<?php

namespace Tests\Unit\Data;

use App\Data\IntegrationSettings\YandexMetrikaIntegrationSettingsData;
use PHPUnit\Framework\Attributes\Test;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class YandexMetrikaIntegrationSettingsPackageTest extends TestCase
{
    #[Test]
    public function test_reports_package_for_context_and_seo(): void
    {
        $context = YandexMetrikaIntegrationSettingsData::reportsPackageFor(ProjectType::CONTEXT_AD->value);
        $this->assertTrue($context['goals_utm']);
        $this->assertTrue($context['goals_conversions']);
        $this->assertTrue($context['goals_direct_summary']);
        $this->assertFalse($context['goals_search_engines']);
        $this->assertFalse($context['visits_geo']);

        $seo = YandexMetrikaIntegrationSettingsData::reportsPackageFor(ProjectType::SEO_PROMOTION->value);
        $this->assertTrue($seo['goals_search_engines']);
        $this->assertTrue($seo['visits_search_engines']);
        $this->assertTrue($seo['visits_search_queries']);
        $this->assertTrue($seo['visits_geo']);
        $this->assertFalse($seo['goals_utm']);
    }

    #[Test]
    public function test_resolve_goals_count_source_from_legacy_reports(): void
    {
        $this->assertSame(
            YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_DIRECT_SUMMARY,
            YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource([
                'reports' => [
                    'goals_direct_summary' => true,
                    'goals_utm' => false,
                ],
            ]),
        );

        $this->assertSame(
            YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_UTM,
            YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource([
                'reports' => [
                    'goals_utm' => true,
                    'goals_direct_summary' => true,
                ],
            ]),
        );

        $this->assertSame(
            YandexMetrikaIntegrationSettingsData::GOALS_COUNT_SOURCE_DIRECT_SUMMARY,
            YandexMetrikaIntegrationSettingsData::resolveGoalsCountSource([
                'goals_count_source' => 'direct_summary',
                'reports' => [
                    'goals_utm' => true,
                    'goals_direct_summary' => true,
                ],
            ]),
        );
    }
}
