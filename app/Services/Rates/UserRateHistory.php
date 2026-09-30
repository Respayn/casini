<?php

namespace App\Services\Rates;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ставка сотрудника на конкретный день: история rate_user + значения rate_values.start_date.
 */
class UserRateHistory
{
    /**
     * @param  array<int, list<array{rate_id: int, assigned_at: string}>>  $assignments  пользователь => назначения по возрастанию даты
     * @param  array<int, array{name: string, values: list<array{value: float, start_date: string}>}>  $rates  ставка => имя и значения по возрастанию даты
     */
    public function __construct(
        private readonly array $assignments = [],
        private readonly array $rates = [],
    ) {}

    /**
     * @param  iterable<int>  $userIds
     */
    public static function forUsers(iterable $userIds): self
    {
        $ids = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return new self;
        }

        $assignmentRows = DB::table('rate_user')
            ->whereIn('user_id', $ids)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['user_id', 'rate_id', 'created_at']);

        $assignments = [];
        foreach ($assignmentRows as $row) {
            $assignments[(int) $row->user_id][] = [
                'rate_id' => (int) $row->rate_id,
                'assigned_at' => Carbon::parse($row->created_at)->toDateString(),
            ];
        }

        $rateIds = $assignmentRows->pluck('rate_id')->map(fn ($id) => (int) $id)->unique()->values();
        $rates = [];

        if ($rateIds->isNotEmpty()) {
            foreach (DB::table('rates')->whereIn('id', $rateIds)->pluck('name', 'id') as $id => $name) {
                $rates[(int) $id] = ['name' => (string) $name, 'values' => []];
            }

            $valueRows = DB::table('rate_values')
                ->whereIn('rate_id', $rateIds)
                ->orderBy('start_date')
                ->orderBy('id')
                ->get(['rate_id', 'value', 'start_date']);

            foreach ($valueRows as $row) {
                if (isset($rates[(int) $row->rate_id])) {
                    $rates[(int) $row->rate_id]['values'][] = [
                        'value' => (float) $row->value,
                        'start_date' => Carbon::parse($row->start_date)->toDateString(),
                    ];
                }
            }
        }

        return new self($assignments, $rates);
    }

    /**
     * @return array{name: string, value: float}|null
     */
    public function rateAt(int $userId, string $date): ?array
    {
        $rateId = $this->pickByDate($this->assignments[$userId] ?? [], 'assigned_at', $date)['rate_id'] ?? null;

        if ($rateId === null || ! isset($this->rates[$rateId])) {
            return null;
        }

        $rate = $this->rates[$rateId];
        $value = $this->pickByDate($rate['values'], 'start_date', $date)['value'] ?? null;

        if ($value === null) {
            return null;
        }

        return ['name' => $rate['name'], 'value' => $value];
    }

    /**
     * Последняя запись не позже даты; если все записи позже, берётся самая ранняя.
     *
     * @param  list<array<string, mixed>>  $items  отсортированы по возрастанию $key
     * @return array<string, mixed>|null
     */
    private function pickByDate(array $items, string $key, string $date): ?array
    {
        if ($items === []) {
            return null;
        }

        $picked = null;
        foreach ($items as $item) {
            if ($item[$key] > $date) {
                break;
            }
            $picked = $item;
        }

        return $picked ?? $items[0];
    }
}
