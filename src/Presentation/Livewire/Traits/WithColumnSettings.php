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
     * @param ColumnSetting[]|null $saved
     * @return array<int, array{key: string, label: string, is_visible: bool, order: int}>
     */
    private function mergeColumnsWithDefaults(?array $saved): array
    {
        $defaults = $this->getDefaultColumns();

        if ($saved === null) {
            return array_map(
                fn(array $col, int $i) => [
                    'key' => $col['key'],
                    'label' => $col['label'],
                    'is_visible' => true,
                    'order' => $i
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
                    'order' => $s->order
                ];
            } else {
                // Новый столбец, которого нет в сохранённых настройках
                $result[] = [
                    'key'        => $key,
                    'label' => $default['label'],
                    'is_visible' => true,
                    'order'      => $maxOrder++,
                ];
            }
        }

        usort($result, fn($a, $b) => $a['order'] <=> $b['order']);

        return array_values($result);
    }

    #[Computed]
    public function visibleColumns(): array
    {
        return array_values(
            array_filter($this->columnSettings, fn(array $col) => $col['is_visible']),
        );
    }

    private function takeColumnSettingsSnapshot(): void
    {
        $this->columnSettingsSnapshot = $this->columnSettings;
    }

    public function sortColumn(string $key, int $newPosition): void
    {
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
                fn(array $col, int $i) => [...$col, 'order' => $i],
                $this->columnSettings,
                array_keys($this->columnSettings),
            ),
        );
    }

    public function applyColumnSettings(): void
    {
        $validKeys = array_column($this->getDefaultColumns(), 'key');

        $settings = [];
        $order = 0;

        foreach ($this->columnSettings as $col) {
            if (! in_array($col['key'], $validKeys, true)) {
                continue;
            }

            $settings[] = new ColumnSetting(
                key: $col['key'],
                isVisible: (bool) $col['is_visible'],
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
    }

    public function revertColumnSettings(): void
    {
        $this->columnSettings = $this->columnSettingsSnapshot;
    }

    public function toggleColumnVisibility(string $key): void
    {
        foreach ($this->columnSettings as $index => $column) {
            if ($column['key'] === $key) {
                $this->columnSettings[$index]['is_visible'] = !$column['is_visible'];
                break;
            }
        }
    }
}
