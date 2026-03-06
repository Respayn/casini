<?php

namespace Src\Application\Reports\Generate;

class ReportDataBuilder
{
    private array $values = [];
    private array $tables = [];
    private array $lists = [];

    public function value(string $key, string $value): self
    {
        $this->values[$key] = $value;
        return $this;
    }

    public function values(array $values): self
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    public function table(string $key, array $rows): self
    {
        $this->tables[$key] = $rows;
        return $this;
    }

    public function list(string $key, array $items): self
    {
        $this->lists[$key] = $items;
        return $this;
    }

    public function build(): ReportData
    {
        return new ReportData(
            values: $this->values,
            tables: $this->tables,
            lists: $this->lists
        );
    }
}
