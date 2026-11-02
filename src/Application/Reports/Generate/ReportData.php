<?php

namespace Src\Application\Reports\Generate;

class ReportData
{
    /**
     * @param  list<array{phraseKey: string, positionKey: string, rows: list<array{phrase: string, position: string, series?: list<string>}>}>  $keywordPositionPairs
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
