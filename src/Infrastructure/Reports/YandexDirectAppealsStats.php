<?php

namespace Src\Infrastructure\Reports;

use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectCampaign;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;

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
     * @param  array<string, string>  $utmCampaignLabels  номер кампании => подпись из клиенто-проекта
     * @return array{
     *     campaign_names: list<string>,
     *     rows: list<array{goal: string, values: list<string>}>,
     *     totals: list<string>
     * }
     */
    public static function build(
        array $directDailyStats,
        array $goalCampaignStats,
        array $utmCampaignLabels = [],
    ): array {
        $matched = DirectCampaignGoalMatcher::match($directDailyStats, $goalCampaignStats);

        return self::renderMatrix($directDailyStats, $matched['matrix'], $utmCampaignLabels);
    }

    /**
     * Матрица из целей UTM: utm_campaign сопоставляется с campaign_id Директа.
     *
     * @param  list<YandexDirectCampaignStats>  $directDailyStats
     * @param  list<YandexMetrikaGoalUtm>  $goalsUtm
     * @param  array<string, string>  $utmCampaignLabels
     * @return array{
     *     campaign_names: list<string>,
     *     rows: list<array{goal: string, values: list<string>}>,
     *     totals: list<string>
     * }
     */
    public static function buildFromUtmGoals(
        array $directDailyStats,
        array $goalsUtm,
        array $utmCampaignLabels = [],
    ): array {
        $campaignIds = [];
        foreach ($directDailyStats as $stats) {
            $campaignId = $stats->getCampaignId();
            if ($campaignId !== null && $campaignId > 0) {
                $campaignIds[(string) $campaignId] = $campaignId;
            }
        }

        $matrix = [];
        foreach ($goalsUtm as $goal) {
            $goalName = trim($goal->getGoalName());
            if ($goalName === '') {
                $goalName = 'Цель';
            }
            $utmCampaign = trim((string) ($goal->getUtmCampaign() ?? ''));
            if ($utmCampaign === '' || ! isset($campaignIds[$utmCampaign])) {
                continue;
            }

            $campaignId = $campaignIds[$utmCampaign];
            $matrix[$goalName][$campaignId] = ($matrix[$goalName][$campaignId] ?? 0) + 1;
        }

        return self::renderMatrix($directDailyStats, $matrix, $utmCampaignLabels);
    }

    /**
     * @param  list<YandexDirectCampaignStats>  $directDailyStats
     * @param  array<string, array<int, int>>  $matrix
     * @param  array<string, string>  $utmCampaignLabels
     * @return array{
     *     campaign_names: list<string>,
     *     rows: list<array{goal: string, values: list<string>}>,
     *     totals: list<string>
     * }
     */
    private static function renderMatrix(
        array $directDailyStats,
        array $matrix,
        array $utmCampaignLabels,
    ): array {
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

        foreach ($campaigns as $campaignId => $directName) {
            $campaigns[$campaignId] = CampaignDisplayName::resolve(
                (int) $campaignId,
                $directName,
                $utmCampaignLabels,
            );
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
