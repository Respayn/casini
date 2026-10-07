<?php

namespace App\Domain\Statistics\Services;

use App\Models\StatisticsProjectMonthlyBonus;
use Illuminate\Support\Carbon;

/**
 * Снимки колонки «Бонусы и гарантии»: не пересчитываем при смене настроек,
 * пока пользователь не нажал «Обновить данные».
 */
class StatisticsMonthlyBonusSnapshotService
{
    /**
     * @return array{kind: string, value?: float}|null
     */
    public function find(int $projectId, int $year, int $month): ?array
    {
        $row = StatisticsProjectMonthlyBonus::query()
            ->where('project_id', $projectId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return $row?->toBonusPayload();
    }

    /**
     * @param  array{kind: string, value?: float}  $bonuses
     */
    public function upsert(int $projectId, int $year, int $month, array $bonuses): void
    {
        $kind = $bonuses['kind'] ?? 'dash';
        if ($kind === 'dash') {
            return;
        }

        StatisticsProjectMonthlyBonus::query()->updateOrCreate(
            [
                'project_id' => $projectId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'kind' => $kind,
                'value' => $kind === 'amount' ? (float) ($bonuses['value'] ?? 0) : null,
                'calculated_at' => Carbon::now(),
            ],
        );
    }

    /**
     * Если есть снимок и не force — вернуть его; иначе сохранить live-расчёт.
     *
     * @param  array{kind: string, value?: float}  $computedBonuses
     * @return array{kind: string, value?: float}
     */
    public function resolve(
        int $projectId,
        Carbon $month,
        array $computedBonuses,
        bool $forceRecalculate = false,
    ): array {
        $kind = $computedBonuses['kind'] ?? 'dash';
        $year = (int) $month->format('Y');
        $monthNum = (int) $month->format('n');

        if ($kind === 'dash') {
            if ($forceRecalculate) {
                $this->delete($projectId, $year, $monthNum);
            }

            return $computedBonuses;
        }

        if (! $forceRecalculate) {
            $existing = $this->find($projectId, $year, $monthNum);
            if ($existing !== null) {
                return $existing;
            }
        }

        $this->upsert($projectId, $year, $monthNum, $computedBonuses);

        return $computedBonuses;
    }

    public function delete(int $projectId, int $year, int $month): void
    {
        StatisticsProjectMonthlyBonus::query()
            ->where('project_id', $projectId)
            ->where('year', $year)
            ->where('month', $month)
            ->delete();
    }
}
