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
     * Создает или обновляет проект.
     *
     * @param ProjectData $data
     * @param int|null $projectId
     * @return Project
     */
    public function createOrUpdateProject(ProjectData $data): Project
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

            return $project;
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
        $project->assistants()->sync($assistantIds);
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
     * @param Project $project
     * @param BonusData $bonusData
     * @return void
     */
    public function saveBonusSettings(Project $project, BonusData $bonusData): void
    {
        $bonusCondition = ProjectBonusCondition::updateOrCreate(
            ['project_id' => $project->id],
            [
                'bonuses_enabled' => $bonusData->bonusesEnabled,
                'calculate_in_percentage' => $bonusData->calculateInPercentage,
                'client_payment' => $bonusData->clientPayment,
                'start_month' => $bonusData->startMonth,
            ]
        );

        // Удаляем старые интервалы
        $bonusCondition->intervals()->delete();

        // Добавляем новые интервалы
        foreach ($bonusData->intervals as $interval) {
            $bonusCondition->intervals()->create([
                'from_percentage' => $interval['fromPercentage'],
                'to_percentage' => $interval['toPercentage'],
                'bonus_amount' => $interval['bonusAmount'] ?? null,
                'bonus_percentage' => $interval['bonusPercentage'] ?? null,
            ]);
        }
    }

    /**
     * Рассчитывает бонусы на основе процентного выполнения.
     *
     * @param ProjectBonusCondition $bonusCondition
     * @param float $performancePercentage
     * @return float
     */
    public function calculateBonuses(ProjectBonusCondition $bonusCondition, float $performancePercentage): float
    {
        if (!$bonusCondition->bonuses_enabled) {
            return 0.0; // Бонусы не включены
        }

        $interval = $bonusCondition->intervals()
            ->where('from_percentage', '<=', $performancePercentage)
            ->where('to_percentage', '>=', $performancePercentage)
            ->first();

        if ($interval) {
            if ($bonusCondition->calculate_in_percentage) {
                $bonusPercentage = $interval->bonus_percentage ?? 0.0;
                return $bonusCondition->client_payment * ($bonusPercentage / 100);
            } else {
                return $interval->bonus_amount ?? 0.0;
            }
        }

        return 0.0;
    }
}
