<?php

namespace App\Services;

use App\Data\BonusData;
use App\Data\ProjectData;
use App\Models\Project;
use App\Models\ProjectBonusCondition;
use App\Models\ProjectFieldHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectService
{
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
            'bonusCondition.intervals',
        ])->findOrFail($projectId);

//        dd($project->bonusCondition->intervals);
//        dd(ProjectData::from($project));

        return ProjectData::from($project);
    }

    /**
     * Создает или обновляет проект.
     *
     * @param ProjectData $data
     * @param int|null $projectId
     * @return Project
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
                // Можно добавить запись истории создания, если это необходимо
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
     * Синхронизирует помощников проекта.
     *
     * @param Project $project
     * @param array $assistantIds
     * @return void
     */
    public function syncAssistants(Project $project, array $assistantIds): void
    {
        // TODO: синхронизация помощников
//        $project->assistants()->sync($assistantIds);
    }

    /**
     * Синхронизирует регионы продвижения проекта.
     *
     * @param Project $project
     * @param array $regionIds
     * @return void
     */
    public function syncPromotionRegions(Project $project, array $regionIds): void
    {
        $project->promotionRegions()->sync($regionIds);
    }

    /**
     * Синхронизирует тематики продвижения проекта.
     *
     * @param Project $project
     * @param array $topicIds
     * @return void
     */
    public function syncPromotionTopics(Project $project, array $topicIds): void
    {
        $project->promotionTopics()->sync($topicIds);
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
}
