<?php

namespace App\Services\Channels;

use App\Enums\LaborRole;
use App\Models\Bitrix24DailyLabor;
use App\Services\Rates\UserRateHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Часы Битрикс24 за период отчёта Каналов: месяц берётся из названия задачи, часы × ставка сотрудника на день записи.
 */
class ChannelLaborSpendingsService
{
    /**
     * @param  Collection<int, int>|list<int>  $projectIds
     * @return array<int, array<string, array{hours: float, sum: float}>> проект => ключ колонки => часы / ₽
     */
    public function forProjects(Collection|array $projectIds, Carbon $from, Carbon $to): array
    {
        $ids = collect($projectIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $rows = Bitrix24DailyLabor::query()
            ->whereIn('project_id', $ids)
            ->whereBetween('report_month', [$from->copy()->startOfMonth()->toDateString(), $to->toDateString()])
            ->get(['project_id', 'date', 'user_id', 'role', 'seconds']);

        return self::aggregate($rows, UserRateHistory::forUsers($rows->pluck('user_id')));
    }

    /**
     * @param  iterable<Bitrix24DailyLabor>  $rows
     * @return array<int, array<string, array{hours: float, sum: float}>>
     */
    public static function aggregate(iterable $rows, UserRateHistory $rates): array
    {
        $result = [];

        foreach ($rows as $row) {
            $role = $row->role instanceof LaborRole ? $row->role->value : (string) $row->role;
            $day = $row->date instanceof Carbon ? $row->date->toDateString() : (string) $row->date;
            $hours = $row->seconds / 3600;
            $rate = $rates->rateAt((int) $row->user_id, $day)['value'] ?? 0.0;

            $cell = $result[(int) $row->project_id][$role] ?? ['hours' => 0.0, 'sum' => 0.0];
            $cell['hours'] += $hours;
            $cell['sum'] += $hours * $rate;
            $result[(int) $row->project_id][$role] = $cell;
        }

        foreach ($result as $projectId => $cells) {
            foreach ($cells as $role => $cell) {
                $result[$projectId][$role] = [
                    'hours' => round($cell['hours'], 2),
                    'sum' => round($cell['sum'], 2),
                ];
            }
        }

        return $result;
    }
}
