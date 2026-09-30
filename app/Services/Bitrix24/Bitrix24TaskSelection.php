<?php

namespace App\Services\Bitrix24;

use Illuminate\Support\Carbon;

/**
 * Какие задачи Битрикс24 относятся к клиенто-проекту: корневая задача из URL и фильтр по названию.
 */
final class Bitrix24TaskSelection
{
    /**
     * Формы названия месяца: именительный, родительный и предложный падеж.
     */
    private const MONTH_FORMS = [
        1 => 'январ[ьяе]',
        2 => 'феврал[ьяе]',
        3 => 'март[ае]?',
        4 => 'апрел[ьяе]',
        5 => 'ма[йяе]',
        6 => 'июн[ьяе]',
        7 => 'июл[ьяе]',
        8 => 'август[ае]?',
        9 => 'сентябр[ьяе]',
        10 => 'октябр[ьяе]',
        11 => 'ноябр[ьяе]',
        12 => 'декабр[ьяе]',
    ];

    public static function taskIdFromUrl(string $url): ?int
    {
        if (preg_match('#/task/view/(\d+)#', $url, $matches) !== 1) {
            return null;
        }

        $id = (int) $matches[1];

        return $id > 0 ? $id : null;
    }

    /**
     * Пустой запрос: подходит любая задача. Иначе в названии должен быть хотя бы один кусок из списка через запятую.
     */
    public static function titleMatches(string $title, string $searchQuery): bool
    {
        $needles = array_values(array_filter(
            array_map('trim', explode(',', $searchQuery)),
            fn (string $needle) => $needle !== '',
        ));

        if ($needles === []) {
            return true;
        }

        foreach ($needles as $needle) {
            if (mb_stripos($title, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Месяц отчёта по названию задачи («SEO подзадача, октябрь 2026»). Без месяца в названии: месяц дня работы.
     * Без года: ближайший к дню работы год (декабрь в январе = прошлый год).
     */
    public static function reportMonth(string $title, Carbon $workDate): Carbon
    {
        $found = null;

        foreach (self::MONTH_FORMS as $month => $form) {
            if (preg_match('/(?<!\p{L})'.$form.'(?!\p{L})(?:\s+(\d{4}))?/iu', $title, $matches, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            if ($found === null || $matches[0][1] < $found['offset']) {
                $found = [
                    'offset' => $matches[0][1],
                    'month' => $month,
                    'year' => isset($matches[1]) ? (int) $matches[1][0] : null,
                ];
            }
        }

        $workMonth = $workDate->copy()->startOfMonth()->startOfDay();

        if ($found === null) {
            return $workMonth;
        }

        if ($found['year'] !== null) {
            return Carbon::create($found['year'], $found['month'], 1)->startOfDay();
        }

        $candidates = array_map(
            fn (int $year) => Carbon::create($year, $found['month'], 1)->startOfDay(),
            [$workDate->year - 1, $workDate->year, $workDate->year + 1],
        );

        usort($candidates, fn (Carbon $a, Carbon $b) => abs($a->diffInMonths($workMonth)) <=> abs($b->diffInMonths($workMonth)));

        return $candidates[0];
    }
}
