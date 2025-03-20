<?php

namespace App\Services;

use App\Data\BonusData;
use App\Data\ProjectData;
use App\Models\Project;
use App\Models\ProjectBonusCondition;
use App\Models\ProjectFieldHistory;
use App\Repositories\Interfaces\ProjectUtmMappingRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function __construct(
        public ProjectUtmMappingRepositoryInterface $utmMappingRepository,
    ) {
    }

    /**
     * Получает данные проекта по его ID.
     *
     * @param int $projectId
     * @return ProjectData
     */
    public function getProjectDataById(int $projectId): ProjectData
    {
        $project = Project::with([
            'assistants',
            'promotionRegions',
            'promotionTopics',
            'utmMappings',
            'bonusCondition.intervals',
        ])->findOrFail($projectId);

        return ProjectData::from($project);
    }

    /**
     * Создает или обновляет проект.
     *
     * @param ProjectData $data
     * @return ProjectData
     */
    public function updateOrCreateProject(ProjectData $data): ProjectData
    {
        return DB::transaction(function () use ($data) {
            if ($data->id) {
                $project = Project::findOrFail($data->id);
                $originalStatus = $project->is_active;
                $project->fill($data->toArray());
                $project->save();

                // Проверяем изменение статуса и сохраняем историю
                if ($originalStatus != $project->is_active) {
                    $this->saveStatusChangeHistory($project, $originalStatus, $project->is_active);
                }
            } else {
                $project = Project::create($data->toArray());
            }

            return ProjectData::from($project);
        });
    }

    private function saveStatusChangeHistory(Project $project, bool $oldStatus, bool $newStatus): void
    {
        ProjectFieldHistory::create([
            'project_id' => $project->id,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'field' => 'is_active',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ]);
    }

    /**
     * Сохраняет бонусные настройки проекта.
     *
     * @param ProjectData $project
     * @param BonusData $bonusData
     * @return void
     */
    public function saveBonusSettings(ProjectData $project, BonusData $bonusData): void
    {
        $bonusCondition = ProjectBonusCondition::updateOrCreate(
            ['project_id' => $project->id],
            [
                'bonuses_enabled' => $bonusData->bonuses_enabled,
                'calculate_in_percentage' => $bonusData->calculate_in_percentage,
                'client_payment' => $bonusData->client_payment,
                'start_month' => $bonusData->start_month,
            ]
        );

        // Удаляем старые интервалы
        $bonusCondition->intervals()->delete();

        // Добавляем новые интервалы
        foreach ($bonusData->intervals as $interval) {
            $bonusCondition->intervals()->create([
                'from_percentage' => $interval->from_percentage,
                'to_percentage' => $interval->to_percentage,
                'bonus_amount' => !$bonusData->calculate_in_percentage ? $interval->bonus_amount : null,
                'bonus_percentage' => $bonusData->calculate_in_percentage ? $interval->bonus_percentage : null,
            ]);
        }
    }

    /**
     * Сохраняет ProjectUtmMapping.
     *
     * @param array $data
     * @param $projectId
     * @return array
     */
    public function saveProjectUtmMapping(array $data, $projectId): void
    {
        $this->utmMappingRepository->saveProjectUtmMappings($data, $projectId);
    }
}
