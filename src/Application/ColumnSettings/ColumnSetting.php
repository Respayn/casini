<?php

namespace Src\Application\ColumnSettings;

readonly class ColumnSetting
{
    public function __construct(
        public string $key,
        public bool $isVisible,
        public int $order
    ) {}
}
