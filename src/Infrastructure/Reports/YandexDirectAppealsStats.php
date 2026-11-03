<?php

namespace Src\Infrastructure\Reports;

use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectCampaign;

/**
 * Матрица «Статистика по обращениям»: цели × кампании Директа.
 */
final class YandexDirectAppealsStats
{
    public const CAMPAIGN = 'yd.appeals.campaign';

    public const GOAL = 'yd.appeals.goal';

    public const VALUE = 'yd.appeals.value';

    public const TOTAL = 'yd.appeals.total';

    /**
     * @return list<string>
     */
    public static function placeholderKeys(): array
    {
        return [
            self::CAMPAIGN,
            self::GOAL,
            self::VALUE,
            self::TOTAL,
        ];
    }

    /**
     * @param  list<YandexDirectCampaignStats>  $directDailyStats
     * @param  list<YandexMetrikaGoalDirectCampaign>  $goalCampaignStats
     * @return array{
     *     campaign_names: list<string>,
     *     rows: list<array{goal: string, values: list<string>}>,
     *     totals: list<string>
     * }
     */
    public static function build(array $directDailyStats, array $goalCampaignStats): array
    {
        $campaigns = [];
        foreach ($directDailyStats as $stats) {
            $campaignId = $stats->getCampaignId();
            if ($campaignId === null || $campaignId <= 0) {
                continue;
            }
            if (! isset($campaigns[$campaignId])) {
                $campaigns[$campaignId] = $stats->getCampaignName();
            } elseif ($campaigns[$campaignId] === '' && $stats->getCampaignName() !== '') {
                $campaigns[$campaignId] = $stats->getCampaignName();
            }
        }

        uasort($campaigns, static fn (string $a, string $b): int => strcmp($a, $b));
        $campaignIds = array_keys($campaigns);
        $campaignNames = array_values($campaigns);

        if ($campaignIds === []) {
            return [
                'campaign_names' => [],
                'rows' => [],
                'totals' => [],
            ];
        }

        $matrix = [];
        foreach ($goalCampaignStats as $item) {
            $goalName = $item->getGoalName();
            $campaignId = $item->getCampaignId();
            if ($goalName === '' || $campaignId <= 0) {
                continue;
            }
            $matrix[$goalName][$campaignId] = ($matrix[$goalName][$campaignId] ?? 0) + $item->getConversions();
        }
        ksort($matrix, SORT_STRING);

        $rows = [];
        $totalsByCampaign = array_fill_keys($campaignIds, 0);
        foreach ($matrix as $goalName => $byCampaign) {
            $values = [];
            foreach ($campaignIds as $campaignId) {
                $count = (int) ($byCampaign[$campaignId] ?? 0);
                $values[] = (string) $count;
                $totalsByCampaign[$campaignId] += $count;
            }
            $rows[] = [
                'goal' => $goalName,
                'values' => $values,
            ];
        }

        $totals = [];
        foreach ($campaignIds as $campaignId) {
            $totals[] = (string) $totalsByCampaign[$campaignId];
        }

        return [
            'campaign_names' => $campaignNames,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
