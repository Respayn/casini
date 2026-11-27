<?php

namespace Src\Presentation\Livewire\Traits;

use Livewire\Attributes\Computed;
use Src\Application\ColumnSettings\ColumnSetting;
use Src\Application\ColumnSettings\Get\GetColumnSettingsQuery;
use Src\Application\ColumnSettings\Get\GetColumnSettingsQueryHandler;
use Src\Application\ColumnSettings\Save\SaveColumnSettingsCommand;
use Src\Application\ColumnSettings\Save\SaveColumnSettingsCommandHandler;

trait WithColumnSettings
{
    /** @var array<int, array{key: string, label: string, is_visible: bool, order: int}> */
    public array $columnSettings = [];

    public array $columnSettingsSnapshot = [];

    /**
     * Уникальный идентификатор таблицы: 'reports', 'templates' и т.д.
     */
    abstract protected function getTableId(): string;

    abstract protected function getUserId(): int;

    /**
     * Столбцы по умолчанию.
     *
     * @return array<int, array{key: string, label: string}>
     *
     * Пример:
     * [
     *     ['key' => 'date',   'label' => 'Дата'],
     *     ['key' => 'client', 'label' => 'Клиент'],
     * ]
     */
    abstract protected function getDefaultColumns(): array;

    /**
     * Ключ колонки, которую при активной группировке нельзя спрятать и сдвинуть.
     */
    protected function pinnedColumnKey(): ?string
    {
        return null;
    }

    public function mountWithColumnSettings(): void
    {
        $this->loadColumnSettings();
        $this->takeColumnSettingsSnapshot();
    }

    private function loadColumnSettings(): void
    {
        $saved = app(GetColumnSettingsQueryHandler::class)->handle(
            new GetColumnSettingsQuery(
                tableId: $this->getTableId(),
                userId: $this->getUserId(),
            ),
        );

        $this->columnSettings = $this->mergeColumnsWithDefaults($saved);
    }

    /**
     * @param  ColumnSetting[]|null  $saved
     * @return array<int, array{key: string, label: string, is_visible: bool, order: int}>
     */
    private function mergeColumnsWithDefaults(?array $saved): array
    {
        $defaults = $this->getDefaultColumns();

        if ($saved === null) {
            return array_map(
                fn (array $col, int $i) => [
                    'key' => $col['key'],
                    'label' => $col['label'],
                    'is_visible' => true,
                    'order' => $i,
                ],
                $defaults,
                array_keys($defaults)
            );
        }

        $savedByKey = [];
        foreach ($saved as $setting) {
            $savedByKey[$setting->key] = $setting;
        }

        $result = [];
        $maxOrder = count($saved);

        foreach ($defaults as $default) {
            $key = $default['key'];

            if (isset($savedByKey[$key])) {
                $s = $savedByKey[$key];
                $result[] = [
                    'key' => $key,
                    'label' => $default['label'],
                    'is_visible' => $s->isVisible,
                    'order' => $s->order,
                ];
            } else {
                // Новый столбец, которого нет в сохранённых настройках
                $result[] = [
                    'key' => $key,
                    'label' => $default['label'],
                    'is_visible' => true,
                    'order' => $maxOrder++,
                ];
            }
        }

        usort($result, fn ($a, $b) => $a['order'] <=> $b['order']);

        return array_values($result);
    }

    #[Computed]
    public function visibleColumns(): array
    {
        $pinnedKey = $this->pinnedColumnKey();
        $columns = array_values(
            array_filter($this->columnSettings, function (array $col) use ($pinnedKey): bool {
                if ($pinnedKey !== null && $col['key'] === $pinnedKey) {
                    return true;
                }

                return (bool) $col['is_visible'];
            }),
        );

        usort($columns, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        if ($pinnedKey === null) {
            return array_values($columns);
        }

        $pinned = null;
        $rest = [];
        foreach ($columns as $column) {
            if ($column['key'] === $pinnedKey) {
                $column['is_visible'] = true;
                $pinned = $column;

                continue;
            }
            $rest[] = $column;
        }

        if ($pinned === null) {
            return array_values($columns);
        }

        return array_values([$pinned, ...$rest]);
    }

    /**
     * Порядок колонок в модалке: закреплённая сверху, остальные как сохранены.
     *
     * @return array<int, array{key: string, label: string, is_visible: bool, order: int}>
     */
    public function columnSettingsForModal(): array
    {
        $pinnedKey = $this->pinnedColumnKey();
        $columns = $this->columnSettings;

        usort($columns, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        if ($pinnedKey === null) {
            return array_values($columns);
        }

        $pinned = null;
        $rest = [];
        foreach ($columns as $column) {
            if ($column['key'] === $pinnedKey) {
                $column['is_visible'] = true;
                $pinned = $column;

                continue;
            }
            $rest[] = $column;
        }

        if ($pinned === null) {
            return array_values($columns);
        }

        return array_values([$pinned, ...$rest]);
    }

    private function takeColumnSettingsSnapshot(): void
    {
        $this->columnSettingsSnapshot = $this->columnSettings;
    }

    public function sortColumn(string $key, int $newPosition): void
    {
        $pinnedKey = $this->pinnedColumnKey();
        if ($pinnedKey !== null && $key === $pinnedKey) {
            return;
        }

        if ($pinnedKey === null) {
            $oldIndex = null;

            foreach ($this->columnSettings as $index => $column) {
                if ($column['key'] === $key) {
                    $oldIndex = $index;
                    break;
                }
            }

            if ($oldIndex === null) {
                return;
            }

            $item = $this->columnSettings[$oldIndex];
            array_splice($this->columnSettings, $oldIndex, 1);
            array_splice($this->columnSettings, $newPosition, 0, [$item]);

            $this->columnSettings = array_values(
                array_map(
                    fn (array $col, int $i) => [...$col, 'order' => $i],
                    $this->columnSettings,
                    array_keys($this->columnSettings),
                ),
            );

            return;
        }

        $movable = array_values(array_filter(
            $this->columnSettings,
            fn (array $col): bool => $col['key'] !== $pinnedKey,
        ));
        usort($movable, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        $fromIndex = null;
        foreach ($movable as $index => $column) {
            if ($column['key'] === $key) {
                $fromIndex = $index;
                break;
            }
        }

        if ($fromIndex === null) {
            return;
        }

        $toIndex = max(0, min($newPosition, count($movable) - 1));
        if ($fromIndex === $toIndex) {
            return;
        }

        $slots = array_column($movable, 'order');
        sort($slots);

        $item = $movable[$fromIndex];
        array_splice($movable, $fromIndex, 1);
        array_splice($movable, $toIndex, 0, [$item]);

        $orderByKey = [];
        foreach ($movable as $index => $column) {
            $orderByKey[$column['key']] = $slots[$index];
        }

        foreach ($this->columnSettings as $index => $column) {
            if (isset($orderByKey[$column['key']])) {
                $this->columnSettings[$index]['order'] = $orderByKey[$column['key']];
            }
        }

        usort($this->columnSettings, fn (array $a, array $b): int => $a['order'] <=> $b['order']);
        $this->columnSettings = array_values($this->columnSettings);
    }

    public function applyColumnSettings(): void
    {
        $validKeys = array_column($this->getDefaultColumns(), 'key');
        $pinnedKey = $this->pinnedColumnKey();

        $columns = $this->columnSettings;
        usort($columns, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        $settings = [];
        $order = 0;

        foreach ($columns as $col) {
            if (! in_array($col['key'], $validKeys, true)) {
                continue;
            }

            $isVisible = (bool) $col['is_visible'];
            if ($pinnedKey !== null && $col['key'] === $pinnedKey) {
                $isVisible = true;
            }

            $settings[] = new ColumnSetting(
                key: $col['key'],
                isVisible: $isVisible,
                order: $order++,
            );
        }

        app(SaveColumnSettingsCommandHandler::class)->handle(
            new SaveColumnSettingsCommand(
                tableId: $this->getTableId(),
                userId: $this->getUserId(),
                settings: $settings,
            ),
        );

        $this->loadColumnSettings();
        $this->takeColumnSettingsSnapshot();
        unset($this->visibleColumns);
    }

    public function revertColumnSettings(): void
    {
        $this->columnSettings = $this->columnSettingsSnapshot;
    }

    public function toggleColumnVisibility(string $key): void
    {
        $pinnedKey = $this->pinnedColumnKey();
        if ($pinnedKey !== null && $key === $pinnedKey) {
            return;
        }

        foreach ($this->columnSettings as $index => $column) {
            if ($column['key'] === $key) {
                $this->columnSettings[$index]['is_visible'] = ! $column['is_visible'];
                break;
            }
        }
    }
}
