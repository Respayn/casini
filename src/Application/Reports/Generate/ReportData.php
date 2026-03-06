<?php

namespace Src\Application\Reports\Generate;

class ReportData
{
    public function __construct(
        private readonly array $values = [],
        private readonly array $tables = [],
        private readonly array $lists = []
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

    public static function builder(): ReportDataBuilder
    {
        return new ReportDataBuilder();
    }
}
