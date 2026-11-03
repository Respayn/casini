<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectCampaign;
use Src\Infrastructure\Reports\YandexDirectAppealsStats;

class YandexDirectAppealsStatsTest extends TestCase
{
    public function test_builds_goal_campaign_matrix_with_zeros_and_totals(): void
    {
        $direct = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания Б',
                'campaign_id' => 200,
                'impressions' => 10,
                'clicks' => 1,
                'cost_with_vat' => 1,
                'cost_without_vat' => 1,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 20,
                'clicks' => 2,
                'cost_with_vat' => 2,
                'cost_without_vat' => 2,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 5,
                'clicks' => 1,
                'cost_with_vat' => 1,
                'cost_without_vat' => 1,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-02',
            ]),
        ];

        $goals = [
            YandexMetrikaGoalDirectCampaign::restore([
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'campaign_id' => 100,
                'campaign_name' => 'Кампания А',
                'month' => '2026-10-01',
                'conversions' => 3,
            ]),
            YandexMetrikaGoalDirectCampaign::restore([
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'campaign_id' => 100,
                'campaign_name' => 'Кампания А',
                'month' => '2026-10-01',
                'conversions' => 2,
            ]),
            YandexMetrikaGoalDirectCampaign::restore([
                'project_id' => 1,
                'goal_name' => 'Звонок',
                'campaign_id' => 200,
                'campaign_name' => 'Кампания Б',
                'month' => '2026-10-01',
                'conversions' => 4,
            ]),
        ];

        $result = YandexDirectAppealsStats::build($direct, $goals);

        $this->assertSame(['Кампания А', 'Кампания Б'], $result['campaign_names']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame('Заявка', $result['rows'][0]['goal']);
        $this->assertSame(['5', '0'], $result['rows'][0]['values']);
        $this->assertSame('Звонок', $result['rows'][1]['goal']);
        $this->assertSame(['0', '4'], $result['rows'][1]['values']);
        $this->assertSame(['5', '4'], $result['totals']);
    }

    public function test_empty_direct_campaigns_returns_empty_matrix(): void
    {
        $result = YandexDirectAppealsStats::build([], [
            YandexMetrikaGoalDirectCampaign::restore([
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'campaign_id' => 100,
                'campaign_name' => 'Кампания А',
                'month' => '2026-10-01',
                'conversions' => 1,
            ]),
        ]);

        $this->assertSame([], $result['campaign_names']);
        $this->assertSame([], $result['rows']);
        $this->assertSame([], $result['totals']);
    }

    public function test_replaces_campaign_header_from_utm_mapping_and_keeps_unmapped(): void
    {
        $direct = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания Б',
                'campaign_id' => 200,
                'impressions' => 10,
                'clicks' => 1,
                'cost_with_vat' => 1,
                'cost_without_vat' => 1,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Кампания А',
                'campaign_id' => 100,
                'impressions' => 20,
                'clicks' => 2,
                'cost_with_vat' => 2,
                'cost_without_vat' => 2,
                'conversions' => 0,
                'goal_name' => null,
                'date' => '2026-10-01',
            ]),
        ];

        $goals = [
            YandexMetrikaGoalDirectCampaign::restore([
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'campaign_id' => 100,
                'campaign_name' => 'Кампания А',
                'month' => '2026-10-01',
                'conversions' => 5,
            ]),
            YandexMetrikaGoalDirectCampaign::restore([
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'campaign_id' => 200,
                'campaign_name' => 'Кампания Б',
                'month' => '2026-10-01',
                'conversions' => 1,
            ]),
        ];

        $withLabels = YandexDirectAppealsStats::build($direct, $goals, [
            '100' => 'МК/Банные чаны/ РФ',
        ]);

        $this->assertSame(['Кампания Б', 'МК/Банные чаны/ РФ'], $withLabels['campaign_names']);
        $this->assertSame(['1', '5'], $withLabels['rows'][0]['values']);
        $this->assertSame(['1', '5'], $withLabels['totals']);

        $withoutLabels = YandexDirectAppealsStats::build($direct, $goals, []);
        $this->assertSame(['Кампания А', 'Кампания Б'], $withoutLabels['campaign_names']);
    }
}
