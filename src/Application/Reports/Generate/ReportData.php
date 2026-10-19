<?php

namespace Src\Application\Reports\Generate;

class ReportData
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        private readonly array $values = [],
        private readonly array $tables = [],
        private readonly array $lists = [],
        private readonly array $images = [],
        private readonly array $conditions = [],
        private readonly array $warnings = [],
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
