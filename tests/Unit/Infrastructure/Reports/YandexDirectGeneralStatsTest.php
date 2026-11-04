<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Domain\Leads\CallibriLead;
use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;
use Src\Infrastructure\Reports\YandexDirectGeneralStats;

class YandexDirectGeneralStatsTest extends TestCase
{
    public function test_aggregates_campaigns_and_matches_leads_by_utm_campaign(): void
    {
        $stats = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 1000,
                'clicks' => 50,
                'cost_with_vat' => 500.0,
                'cost_without_vat' => 400.0,
                'conversions' => 99,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 500,
                'clicks' => 25,
                'cost_with_vat' => 250.0,
                'cost_without_vat' => 200.0,
                'conversions' => 1,
                'goal_name' => null,
                'date' => '2026-10-02',
            ]),
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания Б',
                'campaign_id' => 200,
                'impressions' => 200,
                'clicks' => 0,
                'cost_with_vat' => 10.0,
                'cost_without_vat' => 8.0,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
        ];

        $callibri = [
            CallibriLead::restore([
                'id' => 1,
                'project_id' => 1,
                'external_id' => 'a',
                'date' => '2026-10-01 12:00:00',
                'utm_source' => 'yandex',
                'utm_campaign' => '100',
                'utm_medium' => 'cpc',
                'utm_content' => null,
                'utm_term' => null,
            ]),
            CallibriLead::restore([
                'id' => 2,
                'project_id' => 1,
                'external_id' => 'b',
                'date' => '2026-10-02 12:00:00',
                'utm_source' => 'yandex',
                'utm_campaign' => '100',
                'utm_medium' => 'cpc',
                'utm_content' => null,
                'utm_term' => null,
            ]),
            CallibriLead::restore([
                'id' => 3,
                'project_id' => 1,
                'external_id' => 'c',
                'date' => '2026-10-02 13:00:00',
                'utm_source' => 'yandex',
                'utm_campaign' => null,
                'utm_medium' => 'cpc',
                'utm_content' => null,
                'utm_term' => null,
            ]),
        ];

        $metrika = [
            YandexMetrikaGoalUtm::restore([
                'id' => 1,
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'achieved_date' => '2026-10-01',
                'utm_source' => 'yandex',
                'utm_medium' => 'cpc',
                'utm_campaign' => '100',
                'utm_content' => null,
                'utm_term' => null,
            ]),
        ];

        $result = YandexDirectGeneralStats::build($stats, $callibri, $metrika, true, true);

        $this->assertCount(3, $result['rows']);
        $this->assertSame('Кампания А', $result['rows'][0]['campaign']);
        $this->assertSame('1500', $result['rows'][0]['impressions']);
        $this->assertSame('75', $result['rows'][0]['clicks']);
        $this->assertSame('750.00', $result['rows'][0]['cost']);
        $this->assertSame('5.00%', $result['rows'][0]['ctr']);
        $this->assertSame('10.00', $result['rows'][0]['cpc']);
        $this->assertSame('3', $result['rows'][0]['conversions']);
        $this->assertSame('250.00', $result['rows'][0]['cpl']);

        $this->assertSame('Кампания Б', $result['rows'][1]['campaign']);
        $this->assertSame('0', $result['rows'][1]['conversions']);
        $this->assertSame('-', $result['rows'][1]['cpc']);
        $this->assertSame('-', $result['rows'][1]['cpl']);

        $this->assertSame('Без кампании', $result['rows'][2]['campaign']);
        $this->assertSame('1', $result['rows'][2]['conversions']);

        $this->assertSame('1700', $result['totals'][YandexDirectGeneralStats::TOTAL_IMPRESSIONS]);
        $this->assertSame('75', $result['totals'][YandexDirectGeneralStats::TOTAL_CLICKS]);
        $this->assertSame('760.00', $result['totals'][YandexDirectGeneralStats::TOTAL_COST]);
        $this->assertSame('4', $result['totals'][YandexDirectGeneralStats::TOTAL_CONVERSIONS]);
        $this->assertSame('190.00', $result['totals'][YandexDirectGeneralStats::TOTAL_CPL]);
    }

    public function test_budget_override_and_unmatched_leads_sum_to_statistics_total(): void
    {
        $stats = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 100,
                'clicks' => 10,
                'cost_with_vat' => 50.0,
                'cost_without_vat' => 40.0,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
        ];

        $callibri = [
            CallibriLead::restore([
                'id' => 1,
                'project_id' => 1,
                'external_id' => 'a',
                'date' => '2026-10-01 12:00:00',
                'utm_source' => 'yandex',
                'utm_campaign' => '100',
                'utm_medium' => 'cpc',
                'utm_content' => null,
                'utm_term' => null,
            ]),
            CallibriLead::restore([
                'id' => 2,
                'project_id' => 1,
                'external_id' => 'b',
                'date' => '2026-10-01 13:00:00',
                'utm_source' => 'yandex',
                'utm_campaign' => '999',
                'utm_medium' => 'cpc',
                'utm_content' => null,
                'utm_term' => null,
            ]),
        ];

        $result = YandexDirectGeneralStats::build(
            $stats,
            $callibri,
            [],
            true,
            false,
            [],
            390778.04,
        );

        $this->assertSame('1', $result['rows'][0]['conversions']);
        $this->assertSame('Без кампании', $result['rows'][1]['campaign']);
        $this->assertSame('1', $result['rows'][1]['conversions']);
        $this->assertSame('2', $result['totals'][YandexDirectGeneralStats::TOTAL_CONVERSIONS]);
        $this->assertSame('390778.04', $result['totals'][YandexDirectGeneralStats::TOTAL_COST]);
    }

    public function test_metrika_goals_total_adds_to_totals_without_campaign_split(): void
    {
        $stats = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 100,
                'clicks' => 10,
                'cost_with_vat' => 100.0,
                'cost_without_vat' => 80.0,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
        ];

        $result = YandexDirectGeneralStats::build(
            $stats,
            [],
            [],
            false,
            true,
            [],
            null,
            17,
        );

        $this->assertSame('0', $result['rows'][0]['conversions']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame('17', $result['totals'][YandexDirectGeneralStats::TOTAL_CONVERSIONS]);
    }

    public function test_without_lead_integrations_conversions_and_cpl_are_dash(): void
    {
        $stats = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 100,
                'clicks' => 10,
                'cost_with_vat' => 100.0,
                'cost_without_vat' => 80.0,
                'conversions' => 5,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
        ];

        $result = YandexDirectGeneralStats::build($stats, [], [], false, false);

        $this->assertSame('-', $result['rows'][0]['conversions']);
        $this->assertSame('-', $result['rows'][0]['cpl']);
        $this->assertSame('-', $result['totals'][YandexDirectGeneralStats::TOTAL_CONVERSIONS]);
        $this->assertSame('-', $result['totals'][YandexDirectGeneralStats::TOTAL_CPL]);
        $this->assertSame('10.00', $result['rows'][0]['cpc']);
    }

    public function test_replaces_campaign_name_from_utm_mapping_and_keeps_unmapped(): void
    {
        $stats = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания Б',
                'campaign_id' => 200,
                'impressions' => 200,
                'clicks' => 10,
                'cost_with_vat' => 50.0,
                'cost_without_vat' => 40.0,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 100,
                'clicks' => 5,
                'cost_with_vat' => 25.0,
                'cost_without_vat' => 20.0,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
        ];

        $result = YandexDirectGeneralStats::build(
            $stats,
            [],
            [],
            false,
            false,
            ['100' => 'МК/Банные чаны/ РФ'],
        );

        $this->assertSame('Кампания Б', $result['rows'][0]['campaign']);
        $this->assertSame('МК/Банные чаны/ РФ', $result['rows'][1]['campaign']);
        $this->assertSame('100', $result['rows'][1]['impressions']);

        $withoutLabels = YandexDirectGeneralStats::build($stats, [], [], false, false, []);
        $this->assertSame('Кампания А', $withoutLabels['rows'][0]['campaign']);
        $this->assertSame('Кампания Б', $withoutLabels['rows'][1]['campaign']);
    }
}
