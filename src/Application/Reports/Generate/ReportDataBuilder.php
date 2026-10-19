<?php

namespace Src\Application\Reports\Generate;

class ReportDataBuilder
{
    private array $values = [];

    private array $tables = [];

    private array $lists = [];

    private array $images = [];

    private array $conditions = [];

    /** @var list<string> */
    private array $warnings = [];

    public function value(string $key, string $value): self
    {
        $this->values[$key] = $value;

        return $this;
    }

    public function warning(string $message): self
    {
        $message = trim($message);

        if ($message !== '' && ! in_array($message, $this->warnings, true)) {
            $this->warnings[] = $message;
        }

        return $this;
    }

    public function values(array $values): self
    {
        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function table(string $key, array $headers, array $rows): self
    {
        $this->tables[$key] = [
            'headers' => $headers,
            'rows' => $rows,
        ];

        return $this;
    }

    public function list(string $key, array $items): self
    {
        $this->lists[$key] = $items;

        return $this;
    }

    public function image(string $key, string $path): self
    {
        $this->images[$key] = $path;

        return $this;
    }

    public function condition(string $key, bool $render): self
    {
        $this->conditions[$key] = $render;

        return $this;
    }

    public function build(): ReportData
    {
        return new ReportData(
            values: $this->values,
            tables: $this->tables,
            lists: $this->lists,
            images: $this->images,
            conditions: $this->conditions,
            warnings: $this->warnings,
        );
    }
}
