<?php

namespace Src\Application\ColumnSettings\Get;

readonly class GetColumnSettingsQuery
{
    public function __construct(
        public string $tableId,
        public string $userId
    ) {}
}
