<?php

namespace Src\Application\Reports\Generate;

class ReportData
{
    public function __construct(
        private readonly array $values = [],
        private readonly array $tables = [],
        private readonly array $lists = [],
        private readonly array $images = [],
        private readonly array $conditions = []
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

    public static function builder(): ReportDataBuilder
    {
        return new ReportDataBuilder();
    }
}
