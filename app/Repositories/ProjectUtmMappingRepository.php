<?php

namespace App\Repositories;

use App\Data\ProjectUtmMappingData;
use App\Models\ProjectUtmMapping;
use App\Repositories\Interfaces\ProjectUtmMappingRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProjectUtmMappingRepository extends EloquentRepository implements ProjectUtmMappingRepositoryInterface
{
    public function model()
    {
        return ProjectUtmMapping::class;
    }

    public function all(array $with = [])
    {
        $utmMappings = ProjectUtmMapping::with($with)->get();
        return ProjectUtmMappingData::collect($utmMappings);
    }

    public function find(int $id): ?ProjectUtmMappingData
    {
        $utmMapping = ProjectUtmMapping::with('project')->find($id);

        if ($utmMapping) {
            return ProjectUtmMappingData::from($utmMapping);
        }

        return null;
    }

    public function findBy(string $column, mixed $value)
    {
        $utmMappings = $this->model()->where($column, $value)->get();
        return ProjectUtmMappingData::collect($utmMappings);
    }

    /**
     * Сохраняет коллекцию ProjectUtmMappingData.
     *
     * @param array $data Массив экземпляров ProjectUtmMappingData
     * @param int $projectId
     * @return void Массив сохраненных экземпляров ProjectUtmMappingData
     */
    public function saveProjectUtmMappings(array $data, int $projectId): void
    {
        DB::transaction(function () use ($data, $projectId) {
            $upsertData = array_map(function (ProjectUtmMappingData $data) use ($projectId) {
                return $data->toUpsertArray();
            }, $data);

            $uniqueBy = ['project_id', 'utm_type', 'utm_value'];
            $updateColumns = ['replacement_value', 'updated_at'];

            if (!empty($upsertData)) {
                ProjectUtmMapping::upsert($upsertData, $uniqueBy, $updateColumns);
            }

            $providedCombinations = collect($upsertData)->map(function($item) {
                return $item['utm_type']->value . '||' . $item['utm_value'];
            })->toArray();

            // Проверяем, есть ли переданные комбинации
            if (!empty($providedCombinations)) {
                // Создаем placeholders для параметров
                $placeholders = implode(',', array_fill(0, count($providedCombinations), '?'));

                // Выполняем удаление записей, которых нет в переданных данных
                ProjectUtmMapping::where('project_id', $projectId)
                    ->whereRaw(
                        "CONCAT(utm_type, '||', utm_value) NOT IN ($placeholders)",
                        $providedCombinations
                    )
                    ->delete();
            } else {
                // Если нет комбинаций, удаляем все записи для данного project_id
                ProjectUtmMapping::where('project_id', $projectId)->delete();
            }
        });
    }

}
