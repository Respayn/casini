<?php

namespace App\Services\YandexSearchApi;

use App\Enums\SearchRegion;
use App\Helpers\PhraseDuplicateHelper;
use App\Models\SearchEngine;
use App\Models\SerpKeyword;
use App\Models\SerpRegion;
use App\Models\SerpTask;
use Illuminate\Support\Facades\DB;

class YandexSearchApiSerpSyncService
{
    /**
     * @param  array<int, array{code?: mixed, phrases?: string[]}>  $regions
     * @return list<int> active serp_task ids
     */
    public function syncFromSettings(int $projectId, array $regions): array
    {
        if (! PhraseDuplicateHelper::isValidForSave($regions)) {
            throw new \InvalidArgumentException('Некорректные регионы/фразы Search API');
        }

        $engine = SearchEngine::query()->firstOrCreate(
            ['code' => 'yandex'],
            [
                'name' => 'Яндекс',
                'base_url' => 'https://yandex.ru',
            ]
        );

        $activeTaskIds = [];

        DB::transaction(function () use ($projectId, $regions, $engine, &$activeTaskIds) {
            $desiredKeywordIds = [];

            foreach ($regions as $regionConfig) {
                $regionCode = (int) ($regionConfig['code'] ?? 0);
                $phrases = $regionConfig['phrases'] ?? [];

                if ($regionCode <= 0 || ! is_array($phrases)) {
                    continue;
                }

                $serpRegion = $this->resolveSerpRegion($engine->id, $regionCode);

                foreach ($phrases as $phrase) {
                    $normalized = trim((string) $phrase);
                    if ($normalized === '') {
                        continue;
                    }

                    $keyword = SerpKeyword::query()->firstOrCreate(
                        [
                            'project_id' => $projectId,
                            'phrase' => $normalized,
                        ]
                    );
                    $desiredKeywordIds[$keyword->id] = true;

                    $task = SerpTask::query()->updateOrCreate(
                        [
                            'project_id' => $projectId,
                            'serp_keyword_id' => $keyword->id,
                            'search_engine_id' => $engine->id,
                            'serp_region_id' => $serpRegion->id,
                        ],
                        [
                            'is_active' => true,
                            'check_frequency' => 'daily',
                        ]
                    );

                    $activeTaskIds[] = (int) $task->id;
                }
            }

            SerpTask::query()
                ->where('project_id', $projectId)
                ->where('search_engine_id', $engine->id)
                ->when(
                    $activeTaskIds !== [],
                    fn ($q) => $q->whereNotIn('id', $activeTaskIds),
                    fn ($q) => $q
                )
                ->update(['is_active' => false]);
        });

        return array_values(array_unique($activeTaskIds));
    }

    private function resolveSerpRegion(int $searchEngineId, int $geoId): SerpRegion
    {
        $existing = SerpRegion::query()
            ->where('search_engine_id', $searchEngineId)
            ->where('geo_id', (string) $geoId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $label = SearchRegion::tryFrom($geoId)?->label() ?? ('Регион '.$geoId);

        return SerpRegion::query()->create([
            'search_engine_id' => $searchEngineId,
            'name' => $label,
            'code' => 'yandex_'.$geoId,
            'language' => 'ru',
            'country_code' => 'RU',
            'geo_id' => (string) $geoId,
        ]);
    }
}
