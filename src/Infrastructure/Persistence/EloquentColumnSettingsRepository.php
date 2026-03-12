<?php

namespace Src\Infrastructure\Persistence;

use App\Models\UserColumnPreference;
use Src\Application\ColumnSettings\ColumnSetting;
use Src\Application\ColumnSettings\ColumnSettingsRepositoryInterface;

class EloquentColumnSettingsRepository implements ColumnSettingsRepositoryInterface
{
    public function find(string $tableId, int $userId): ?array
    {
        $record = UserColumnPreference::query()
            ->where('table_id', $tableId)
            ->where('user_id', $userId)
            ->first();

        if (!$record) {
            return null;
        }

        return array_map(fn(array $item) => new ColumnSetting(
            key: $item['key'],
            isVisible: $item['is_visible'],
            order: $item['order']
        ), $record->settings);
    }

    public function save(string $tableId, int $userId, array $settings): void
    {
        UserColumnPreference::updateOrCreate(
            [
                'table_id' => $tableId,
                'user_id' => $userId
            ],
            [
                'settings' => array_map(fn(ColumnSetting $s) => [
                    'key' => $s->key,
                    'is_visible' => $s->isVisible,
                    'order' => $s->order
                ], $settings)
            ]
        );
    }
}
