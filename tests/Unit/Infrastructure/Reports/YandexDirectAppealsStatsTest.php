<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectCampaign;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;
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

    public function test_matches_direct_summary_goals_by_exact_name_when_ids_differ(): void
    {
        $direct = [
            YandexDirectCampaignStats::restore([
                'project_id' => 1,
                'campaign_name' => 'Выставки /РСЯ',
                'campaign_id' => 112468072,
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
                'campaign_name' => 'Аудитории / Рыба опт',
                'campaign_id' => 103620440,
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
                'goal_name' => 'Novaya_Sdelka',
                'campaign_id' => 203620440,
                'campaign_name' => 'Аудитории / Рыба опт',
                'month' => '2026-10-01',
                'conversions' => 7,
            ]),
            YandexMetrikaGoalDirectCampaign::restore([
                'project_id' => 1,
                'goal_name' => 'Novaya_Sdelka',
                'campaign_id' => 212468072,
                'campaign_name' => 'Выставки /РСЯ',
                'month' => '2026-10-01',
                'conversions' => 18,
            ]),
            YandexMetrikaGoalDirectCampaign::restore([
                'project_id' => 1,
                'goal_name' => 'Novaya_Sdelka',
                'campaign_id' => 999,
                'campaign_name' => 'Чужая кампания',
                'month' => '2026-10-01',
                'conversions' => 5,
            ]),
        ];

        $result = YandexDirectAppealsStats::build($direct, $goals);

        $this->assertSame(['Аудитории / Рыба опт', 'Выставки /РСЯ'], $result['campaign_names']);
        $this->assertSame('Novaya_Sdelka', $result['rows'][0]['goal']);
        $this->assertSame(['7', '18'], $result['rows'][0]['values']);
        $this->assertSame(['7', '18'], $result['totals']);
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

    public function test_builds_matrix_from_utm_goals_matched_by_campaign_id(): void
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
            YandexMetrikaGoalUtm::restore([
                'id' => 1,
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'achieved_date' => '2026-10-01',
                'utm_campaign' => '100',
            ]),
            YandexMetrikaGoalUtm::restore([
                'id' => 2,
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'achieved_date' => '2026-10-02',
                'utm_campaign' => '100',
            ]),
            YandexMetrikaGoalUtm::restore([
                'id' => 3,
                'project_id' => 1,
                'goal_name' => 'Звонок',
                'achieved_date' => '2026-10-01',
                'utm_campaign' => '200',
            ]),
            YandexMetrikaGoalUtm::restore([
                'id' => 4,
                'project_id' => 1,
                'goal_name' => 'Заявка',
                'achieved_date' => '2026-10-01',
                'utm_campaign' => '999',
            ]),
        ];

        $result = YandexDirectAppealsStats::buildFromUtmGoals($direct, $goals);

        $this->assertSame(['Кампания А', 'Кампания Б'], $result['campaign_names']);
        $this->assertSame('Заявка', $result['rows'][0]['goal']);
        $this->assertSame(['2', '0'], $result['rows'][0]['values']);
        $this->assertSame('Звонок', $result['rows'][1]['goal']);
        $this->assertSame(['0', '1'], $result['rows'][1]['values']);
        $this->assertSame(['2', '1'], $result['totals']);
    }

    public function test_utm_empty_goal_name_falls_back_to_default_label(): void
    {
        $direct = [
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
            YandexMetrikaGoalUtm::restore([
                'id' => 1,
                'project_id' => 1,
                'goal_name' => '',
                'achieved_date' => '2026-10-01',
                'utm_campaign' => '100',
            ]),
        ];

        $result = YandexDirectAppealsStats::buildFromUtmGoals($direct, $goals);

        $this->assertSame('Цель', $result['rows'][0]['goal']);
        $this->assertSame(['1'], $result['rows'][0]['values']);
    }
}
