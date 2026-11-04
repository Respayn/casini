<?php

namespace Src\Infrastructure\Reports;

use Src\Domain\Leads\CallibriLead;
use Src\Domain\YandexDirect\YandexDirectCampaignStats;
use Src\Domain\YandexMetrika\YandexMetrikaGoalUtm;

/**
 * Строки «Общая статистика» для Word: кампании Директа + лиды/CPL как в настройках клиенто-проекта.
 */
final class YandexDirectGeneralStats
{
    public const CAMPAIGN = 'yd.stats.campaign';

    public const IMPRESSIONS = 'yd.stats.impressions';

    public const CLICKS = 'yd.stats.clicks';

    public const COST = 'yd.stats.cost';

    public const CTR = 'yd.stats.ctr';

    public const CPC = 'yd.stats.cpc';

    public const CONVERSIONS = 'yd.stats.conversions';

    public const CPL = 'yd.stats.cpl';

    public const TOTAL_IMPRESSIONS = 'yd.stats.total.impressions';

    public const TOTAL_CLICKS = 'yd.stats.total.clicks';

    public const TOTAL_COST = 'yd.stats.total.cost';

    public const TOTAL_CTR = 'yd.stats.total.ctr';

    public const TOTAL_CPC = 'yd.stats.total.cpc';

    public const TOTAL_CONVERSIONS = 'yd.stats.total.conversions';

    public const TOTAL_CPL = 'yd.stats.total.cpl';

    /**
     * @return list<string>
     */
    public static function rowKeys(): array
    {
        return [
            self::CAMPAIGN,
            self::IMPRESSIONS,
            self::CLICKS,
            self::COST,
            self::CTR,
            self::CPC,
            self::CONVERSIONS,
            self::CPL,
        ];
    }

    /**
     * @return list<string>
     */
    public static function totalKeys(): array
    {
        return [
            self::TOTAL_IMPRESSIONS,
            self::TOTAL_CLICKS,
            self::TOTAL_COST,
            self::TOTAL_CTR,
            self::TOTAL_CPC,
            self::TOTAL_CONVERSIONS,
            self::TOTAL_CPL,
        ];
    }

    /**
     * @param  list<YandexDirectCampaignStats>  $dailyStats
     * @param  list<CallibriLead>  $callibriLeads
     * @param  list<YandexMetrikaGoalUtm>  $goalsUtm
     * @param  array<string, string>  $utmCampaignLabels  номер кампании => подпись из клиенто-проекта
     * @return array{
     *     rows: list<array{
     *         campaign: string,
     *         impressions: string,
     *         clicks: string,
     *         cost: string,
     *         ctr: string,
     *         cpc: string,
     *         conversions: string,
     *         cpl: string
     *     }>,
     *     totals: array<string, string>
     * }
     */
    /**
     * @param  array<string, string>  $utmCampaignLabels
     * @param  float|null  $budgetTotalOverride  расход из yandex_direct_daily_spendings (как в Каналах)
     * @param  int|null  $metrikaGoalsTotal  сумма «Директ, сводка» без разреза по кампаниям
     */
    public static function build(
        array $dailyStats,
        array $callibriLeads,
        array $goalsUtm,
        bool $includeCallibri,
        bool $includeMetrika,
        array $utmCampaignLabels = [],
        ?float $budgetTotalOverride = null,
        ?int $metrikaGoalsTotal = null,
    ): array {
        $leadsEnabled = $includeCallibri || $includeMetrika;
        $splitMetrikaByCampaign = $includeMetrika && $metrikaGoalsTotal === null;
        $leadsSplit = ($includeCallibri || $splitMetrikaByCampaign)
            ? self::leadsByCampaign($callibriLeads, $goalsUtm, $includeCallibri, $splitMetrikaByCampaign, $dailyStats)
            : ['matched' => [], 'unmatched' => 0];

        $aggregated = [];
        foreach ($dailyStats as $stats) {
            $campaignId = $stats->getCampaignId();
            $key = $campaignId !== null
                ? 'id:'.$campaignId
                : 'name:'.$stats->getCampaignName();

            if (! isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'campaign_id' => $campaignId,
                    'campaign_name' => $stats->getCampaignName(),
                    'impressions' => 0,
                    'clicks' => 0,
                    'cost' => 0.0,
                ];
            }

            $aggregated[$key]['impressions'] += $stats->getImpressions();
            $aggregated[$key]['clicks'] += $stats->getClicks();
            $aggregated[$key]['cost'] += $stats->getCostWithVat();
            if ($aggregated[$key]['campaign_name'] === '' && $stats->getCampaignName() !== '') {
                $aggregated[$key]['campaign_name'] = $stats->getCampaignName();
            }
        }

        foreach ($aggregated as $key => $item) {
            $aggregated[$key]['campaign_name'] = CampaignDisplayName::resolve(
                $item['campaign_id'],
                $item['campaign_name'],
                $utmCampaignLabels,
            );
        }

        uasort(
            $aggregated,
            static fn (array $a, array $b): int => strcmp($a['campaign_name'], $b['campaign_name'])
        );

        $rows = [];
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalCost = 0.0;
        $totalConversions = 0;

        foreach ($aggregated as $item) {
            $campaignIdStr = $item['campaign_id'] !== null ? (string) $item['campaign_id'] : null;
            $conversions = ($leadsEnabled && $campaignIdStr !== null)
                ? ($leadsSplit['matched'][$campaignIdStr] ?? 0)
                : null;

            $rows[] = self::formatRow(
                $item['campaign_name'],
                $item['impressions'],
                $item['clicks'],
                $item['cost'],
                $conversions,
            );

            $totalImpressions += $item['impressions'];
            $totalClicks += $item['clicks'];
            $totalCost += $item['cost'];
            if ($conversions !== null) {
                $totalConversions += $conversions;
            }
        }

        if ($leadsEnabled && $leadsSplit['unmatched'] > 0) {
            $rows[] = self::formatRow(
                'Без кампании',
                0,
                0,
                0.0,
                $leadsSplit['unmatched'],
            );
            $totalConversions += $leadsSplit['unmatched'];
        }

        if ($budgetTotalOverride !== null) {
            $totalCost = $budgetTotalOverride;
        }

        if ($includeMetrika && $metrikaGoalsTotal !== null) {
            $totalConversions += $metrikaGoalsTotal;
        }

        $totals = self::formatTotals(
            $totalImpressions,
            $totalClicks,
            $totalCost,
            $leadsEnabled ? $totalConversions : null,
        );

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * @param  list<CallibriLead>  $callibriLeads
     * @param  list<YandexMetrikaGoalUtm>  $goalsUtm
     * @param  list<YandexDirectCampaignStats>  $dailyStats
     * @return array{matched: array<string, int>, unmatched: int}
     */
    private static function leadsByCampaign(
        array $callibriLeads,
        array $goalsUtm,
        bool $includeCallibri,
        bool $includeMetrika,
        array $dailyStats = [],
    ): array {
        $campaignIds = [];
        foreach ($dailyStats as $stats) {
            $campaignId = $stats->getCampaignId();
            if ($campaignId !== null && $campaignId > 0) {
                $campaignIds[(string) $campaignId] = true;
            }
        }

        $matched = [];
        $unmatched = 0;

        $consume = static function (?string $utmCampaign) use (&$matched, &$unmatched, $campaignIds): void {
            if ($utmCampaign === null || $utmCampaign === '') {
                $unmatched++;

                return;
            }
            if (isset($campaignIds[$utmCampaign])) {
                $matched[$utmCampaign] = ($matched[$utmCampaign] ?? 0) + 1;
            } else {
                $unmatched++;
            }
        };

        if ($includeCallibri) {
            foreach ($callibriLeads as $lead) {
                $consume($lead->getUtmCampaign());
            }
        }

        if ($includeMetrika) {
            foreach ($goalsUtm as $goal) {
                $consume($goal->getUtmCampaign());
            }
        }

        return [
            'matched' => $matched,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * @return array{
     *     campaign: string,
     *     impressions: string,
     *     clicks: string,
     *     cost: string,
     *     ctr: string,
     *     cpc: string,
     *     conversions: string,
     *     cpl: string
     * }
     */
    private static function formatRow(
        string $campaignName,
        int $impressions,
        int $clicks,
        float $cost,
        ?int $conversions,
    ): array {
        return [
            'campaign' => $campaignName,
            'impressions' => (string) $impressions,
            'clicks' => (string) $clicks,
            'cost' => self::money($cost),
            'ctr' => self::ctr($impressions, $clicks),
            'cpc' => self::cpc($cost, $clicks),
            'conversions' => $conversions === null ? '-' : (string) $conversions,
            'cpl' => $conversions === null ? '-' : self::cpl($cost, $conversions),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function formatTotals(
        int $impressions,
        int $clicks,
        float $cost,
        ?int $conversions,
    ): array {
        return [
            self::TOTAL_IMPRESSIONS => (string) $impressions,
            self::TOTAL_CLICKS => (string) $clicks,
            self::TOTAL_COST => self::money($cost),
            self::TOTAL_CTR => self::ctr($impressions, $clicks),
            self::TOTAL_CPC => self::cpc($cost, $clicks),
            self::TOTAL_CONVERSIONS => $conversions === null ? '-' : (string) $conversions,
            self::TOTAL_CPL => $conversions === null ? '-' : self::cpl($cost, $conversions),
        ];
    }

    private static function money(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    private static function ctr(int $impressions, int $clicks): string
    {
        if ($impressions === 0) {
            return '0%';
        }

        return number_format(round(($clicks / $impressions) * 100, 2), 2, '.', '').'%';
    }

    private static function cpc(float $cost, int $clicks): string
    {
        if ($clicks === 0) {
            return '-';
        }

        return self::money($cost / $clicks);
    }

    private static function cpl(float $cost, int $conversions): string
    {
        if ($conversions <= 0) {
            return '-';
        }

        return self::money($cost / $conversions);
    }
}
