<?php

namespace Src\Infrastructure\Reports;

use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexMetrika\YandexMetrikaGoalDirectCampaign;

/**
 * Стыковка строк «Директ, сводка» по кампаниям с кампаниями Директа:
 * сначала по номеру, затем по точному названию.
 */
final class DirectCampaignGoalMatcher
{
    /**
     * @param  list<YandexDirectCampaignStats>  $dailyStats
     * @param  list<YandexMetrikaGoalDirectCampaign>  $goalCampaignStats
     * @return array{
     *     matched: array<string, int>,
     *     unmatched: int,
     *     matrix: array<string, array<int, int>>
     * }
     */
    public static function match(array $dailyStats, array $goalCampaignStats): array
    {
        $byId = [];
        $byName = [];

        foreach ($dailyStats as $stats) {
            $campaignId = $stats->getCampaignId();
            if ($campaignId === null || $campaignId <= 0) {
                continue;
            }

            $byId[$campaignId] = true;
            $name = trim($stats->getCampaignName());
            if ($name === '') {
                continue;
            }
            $byName[$name][] = $campaignId;
        }

        $matched = [];
        $unmatched = 0;
        $matrix = [];

        foreach ($goalCampaignStats as $item) {
            $conversions = $item->getConversions();
            if ($conversions <= 0) {
                continue;
            }

            $directCampaignId = self::resolveDirectCampaignId($item, $byId, $byName);
            if ($directCampaignId === null) {
                $unmatched += $conversions;

                continue;
            }

            $key = (string) $directCampaignId;
            $matched[$key] = ($matched[$key] ?? 0) + $conversions;

            $goalName = trim($item->getGoalName());
            if ($goalName === '') {
                continue;
            }
            $matrix[$goalName][$directCampaignId] = ($matrix[$goalName][$directCampaignId] ?? 0) + $conversions;
        }

        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
            'matrix' => $matrix,
        ];
    }

    /**
     * @param  array<int, true>  $byId
     * @param  array<string, list<int>>  $byName
     */
    private static function resolveDirectCampaignId(
        YandexMetrikaGoalDirectCampaign $item,
        array $byId,
        array $byName,
    ): ?int {
        $metrikaCampaignId = $item->getCampaignId();
        if ($metrikaCampaignId > 0 && isset($byId[$metrikaCampaignId])) {
            return $metrikaCampaignId;
        }

        $name = trim($item->getCampaignName());
        if ($name === '' || ! isset($byName[$name])) {
            return null;
        }

        $candidates = array_values(array_unique($byName[$name]));
        if (count($candidates) !== 1) {
            return null;
        }

        return $candidates[0];
    }
}
