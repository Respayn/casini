<?php

namespace Src\Application\Reports\Generate;

class ReportData
{
    /**
     * @param  list<array{phraseKey: string, positionKey: string, rows: list<array{phrase: string, position: string, series?: list<string>}>}>  $keywordPositionPairs
     * @param  list<array{
     *     campaign: string,
     *     impressions: string,
     *     clicks: string,
     *     cost: string,
     *     ctr: string,
     *     cpc: string,
     *     conversions: string,
     *     cpl: string
     * }>  $directCampaignStatRows
     * @param  array{
     *     campaign_names: list<string>,
     *     rows: list<array{goal: string, values: list<string>}>,
     *     totals: list<string>
     * }|null  $directAppealsStats
     * @param  array<string, list<string>>  $periodSeries
     * @param  list<string>  $periodColumnLabels
     * @param  list<string>  $warnings
     */
    public function __construct(
        private readonly array $values = [],
        private readonly array $tables = [],
        private readonly array $lists = [],
        private readonly array $images = [],
        private readonly array $conditions = [],
        private readonly array $keywordPositionPairs = [],
        private readonly array $directCampaignStatRows = [],
        private readonly ?array $directAppealsStats = null,
        private readonly array $warnings = [],
        private readonly array $periodSeries = [],
        private readonly array $periodColumnLabels = [],
    ) {}

    public function getValues(): array
    {
        return $this->values;
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function getLists(): array
    {
        return $this->lists;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @return list<array{phraseKey: string, positionKey: string, rows: list<array{phrase: string, position: string, series?: list<string>}>}>
     */
    public function getKeywordPositionPairs(): array
    {
        return $this->keywordPositionPairs;
    }

    /**
     * @return list<array{
     *     campaign: string,
     *     impressions: string,
     *     clicks: string,
     *     cost: string,
     *     ctr: string,
     *     cpc: string,
     *     conversions: string,
     *     cpl: string
     * }>
     */
    public function getDirectCampaignStatRows(): array
    {
        return $this->directCampaignStatRows;
    }

    /**
     * @return array{
     *     campaign_names: list<string>,
     *     rows: list<array{goal: string, values: list<string>}>,
     *     totals: list<string>
     * }|null
     */
    public function getDirectAppealsStats(): ?array
    {
        return $this->directAppealsStats;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getPeriodSeries(): array
    {
        return $this->periodSeries;
    }

    /**
     * @return list<string>
     */
    public function getPeriodColumnLabels(): array
    {
        return $this->periodColumnLabels;
    }

    public function shouldExpandPeriodColumns(): bool
    {
        return count($this->periodColumnLabels) > 1;
    }

    /**
     * @return list<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public static function builder(): ReportDataBuilder
    {
        return new ReportDataBuilder;
    }
}
