<?php

namespace Src\Application\ColumnSettings;

interface ColumnSettingsRepositoryInterface
{
    /**
     * @return ColumnSetting[]|null
     */
    public function find(string $tableId, int $userId): ?array;

    /**
     * @param ColumnSetting[] $settings
     */
    public function save(string $tableId, int $userId, array $settings): void;
}