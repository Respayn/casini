<?php

namespace App\Services;

use App\Data\BonusConditionData;
use App\Models\Project;
use App\Models\ProjectBonusCondition;

class BonusService
{
    /**
     * Сохраняет или обновляет бонусные условие
     *
     * @param Project $project
     * @param BonusConditionData $bonusData
     */
    public function saveBonusSettings(Project $project, BonusConditionData $bonusData): void
    {
        // Сохранение или обновление условий бонусов
        $bonusCondition = ProjectBonusCondition::updateOrCreate(
            ['project_id' => $project->id],
            [
                'bonuses_enabled' => $bonusData->bonusesEnabled,
                'calculateInPercentage' => $bonusData->calculateInPercentage,
                'client_payment' => $bonusData->clientPayment,
                'start_month' => $bonusData->startMonth,
            ]
        );

        // Удаляем старые интервалы
        $bonusCondition->intervals()->delete();

        // Сохраняем новые интервалы
        foreach ($bonusData->intervals as $interval) {
            $bonusCondition->intervals()->create([
                'from_percentage' => $interval->fromPercentage,
                'to_percentage' => $interval->toPercentage,
                'bonus_amount' => $bonusData->calculateInPercentage ? null : $interval->bonusAmount,
                'bonus_percentage' => $bonusData->calculateInPercentage ? $interval->bonusPercentage : null,
            ]);
        }
    }

    /**
     * Рассчитывает сумму бонусов для проекта
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

        $totalBonus = 0.0;

        foreach ($bonusCondition->intervals as $interval) {
            if (
                $performancePercentage >= $interval->from_percentage &&
                $performancePercentage <= $interval->to_percentage
            ) {
                if ($bonusCondition->calculate_in_percentage) {
                    // Рассчитываем бонус как процент от суммы чека
                    $bonusPercentage = $interval->bonus_percentage ?? 0;
                    $totalBonus += ($bonusCondition->client_payment * $bonusPercentage / 100);
                } else {
                    // Рассчитываем фиксированный бонус в рублях
                    $bonusAmount = $interval->bonus_amount ?? 0;
                    $totalBonus += $bonusAmount;
                }
            }
        }

        return $totalBonus;
    }
}
