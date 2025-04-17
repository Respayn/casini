<?php

namespace App\Parsers;

use App\Data\YandexDirect\CampaignStatisticsDTO;
use App\Data\YandexDirect\PerformanceReportDTO;
use Illuminate\Support\Collection;

class YandexDirectReportParser
{
    /**
     * Парсинг основного отчета о производительности
     */
    public function parsePerformanceReport(array $reportData): Collection
    {
        return $this->parseArrayReport($reportData, [
            'required' => ['Impressions', 'Clicks', 'Cost'],
            'dto' => PerformanceReportDTO::class
        ]);
    }

    /**
     * Парсинг статистики по кампании
     */
    public function parseCampaignStatistics(array $reportData): Collection
    {
        return $this->parseArrayReport($reportData, [
            'required' => ['Date', 'CampaignId', 'Clicks', 'Cost'],
            'dto' => CampaignStatisticsDTO::class
        ]);
    }

    /**
     * Универсальный парсер массива данных
     */
    protected function parseArrayReport(array $data, array $config): Collection
    {
        if (empty($data)) {
            return collect();
        }

        $this->validateFields(array_keys($data[0]), $config['required']);

        return collect($data)->map(function ($item) use ($config) {
            return new $config['dto'](...$this->mapItem($item, $config['required']));
        });
    }

    /**
     * Валидация наличия обязательных полей
     */
    private function validateFields(array $existingFields, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!in_array($field, $existingFields)) {
                throw new \RuntimeException("Missing required field in report: {$field}");
            }
        }
    }

    /**
     * Преобразование элемента массива в аргументы для DTO
     */
    private function mapItem(array $item, array $fields): array
    {
        return array_map(function ($field) use ($item) {
            return $this->castValue($field, $item[$field] ?? null);
        }, $fields);
    }

    /**
     * Приведение типов данных
     */
    private function castValue(string $field, $value)
    {
        return match(true) {
            in_array($field, ['Impressions', 'Clicks']) => (int)$value,
            in_array($field, ['Cost']) => (float)$value,
            default => $value
        };
    }
}
