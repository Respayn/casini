<?php

namespace App\Services;

use App\Data\BonusConditionData;
use App\Models\Project;
use App\Models\ProjectBonusCondition;

class BonusService
{
    /**
     * Сохраняет или обновляет бонусные условие
     */
    public function saveBonusSettings(Project $project, BonusConditionData $bonusData): void
    {
        // Сохранение или обновление условий бонусов
        $bonusCondition = ProjectBonusCondition::updateOrCreate(
            ['project_id' => $project->id],
            [
                'bonuses_enabled' => $bonusData->bonuses_enabled,
                'calculateInPercentage' => $bonusData->calculate_in_percentage,
                'client_payment' => $bonusData->client_payment,
                'start_month' => $bonusData->start_month,
            ]
        );

        // Удаляем старые интервалы
        $bonusCondition->intervals()->delete();

        // Сохраняем новые интервалы
        foreach ($bonusData->intervals as $interval) {
            $bonusCondition->intervals()->create([
                'from_percentage' => $interval->fromPercentage,
                'to_percentage' => $interval->toPercentage,
                'bonus_amount' => $bonusData->calculate_in_percentage ? null : $interval->bonusAmount,
                'bonus_percentage' => $bonusData->calculate_in_percentage ? $interval->bonusPercentage : null,
            ]);
        }
    }

    /**
     * Рассчитывает сумму бонусов для проекта
     */
    public function calculateBonuses(ProjectBonusCondition $bonusCondition, float $performancePercentage): float
    {
        if (! $bonusCondition->bonuses_enabled) {
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

    /**
     * Максимальный доступный бонус по настройкам клиенто-проекта (₽).
     * Берётся наибольшее значение среди интервалов; при выключенных бонусах или пустых условиях — null.
     */
    public function resolveMaxBonusAmount(?ProjectBonusCondition $bonusCondition): ?float
    {
        if ($bonusCondition === null || ! $bonusCondition->bonuses_enabled) {
            return null;
        }

        if ($bonusCondition->relationLoaded('intervals') === false) {
            $bonusCondition->load('intervals');
        }

        $intervals = $bonusCondition->intervals;
        if ($intervals === null || $intervals->isEmpty()) {
            return null;
        }

        $amounts = [];
        foreach ($intervals as $interval) {
            if ($bonusCondition->calculate_in_percentage) {
                if (! is_numeric($bonusCondition->client_payment) || ! is_numeric($interval->bonus_percentage)) {
                    continue;
                }

                $amounts[] = (float) $bonusCondition->client_payment * ((float) $interval->bonus_percentage / 100);
            } else {
                if (! is_numeric($interval->bonus_amount)) {
                    continue;
                }

                $amounts[] = (float) $interval->bonus_amount;
            }
        }

        if ($amounts === []) {
            return null;
        }

        return round(max($amounts), 2);
    }
}
