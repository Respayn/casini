<?php

namespace Src\Application\ColumnSettings\Save;

use Src\Domain\ColumnSettings\ColumnSetting;

readonly class SaveColumnSettingsCommand
{
    /**
     * @param ColumnSetting[] $settings
     */
    public function __construct(
        public string $tableId,
        public string $userId,
        public array $settings
    ) {}
}
