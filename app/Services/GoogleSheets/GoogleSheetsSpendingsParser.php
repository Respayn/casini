<?php

namespace App\Services\GoogleSheets;

use Carbon\Carbon;
use Illuminate\Support\Str;

class GoogleSheetsSpendingsParser
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{hours: float, sum: float}
     */
    public function parseProgrammingSheet(array $rows, Carbon $month): array
    {
        return $this->parseQuantityAndSumSheet(
            $rows,
            $month,
            unitsColumnMatchers: [
                'объем итого, час',
                'объем итого час',
                'час',
                'часы',
            ],
            sumColumnMatchers: [
                'итоговый ценник',
                'ценник',
                'сумма',
                'итого',
            ],
        );
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{hours: float, sum: float}
     */
    public function parseCopyrightingSheet(array $rows, Carbon $month): array
    {
        return $this->parseQuantityAndSumSheet(
            $rows,
            $month,
            unitsColumnMatchers: [
                'объем итого, знак',
                'объем итого знак',
            ],
            sumColumnMatchers: [
                'итоговый ценник',
            ],
        );
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function parseSeoLinksSheet(array $rows, Carbon $month): float
    {
        if ($rows === []) {
            return 0.0;
        }

        $headerRowIndex = $this->findHeaderRowIndex($rows);
        $headers = $rows[$headerRowIndex] ?? [];
        $monthColumnIndex = $this->findMonthColumnIndex($headers, $month);

        if ($monthColumnIndex === null) {
            return 0.0;
        }

        $totalRowIndex = $this->findRowIndexByLabel($rows, 'итого', $headerRowIndex + 1);

        if ($totalRowIndex === null) {
            return 0.0;
        }

        return $this->parseNumber($rows[$totalRowIndex][$monthColumnIndex] ?? null);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, string>  $unitsColumnMatchers
     * @param  array<int, string>  $sumColumnMatchers
     * @return array{hours: float, sum: float}
     */
    private function parseQuantityAndSumSheet(
        array $rows,
        Carbon $month,
        array $unitsColumnMatchers,
        array $sumColumnMatchers,
    ): array {
        if ($rows === []) {
            return ['hours' => 0.0, 'sum' => 0.0];
        }

        $headerRowIndex = $this->findHeaderRowIndex($rows);
        $headers = $rows[$headerRowIndex] ?? [];

        $dateColumnIndex = $this->findColumnIndex($headers, ['статус оплаты']);
        $unitsColumnIndex = $this->findColumnIndex($headers, $unitsColumnMatchers);
        $sumColumnIndex = $this->findColumnIndex($headers, $sumColumnMatchers);

        if ($dateColumnIndex === null || $unitsColumnIndex === null || $sumColumnIndex === null) {
            return ['hours' => 0.0, 'sum' => 0.0];
        }

        $hours = 0.0;
        $sum = 0.0;

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i] ?? [];

            if (! $this->rowDateMatchesMonth($row[$dateColumnIndex] ?? null, $month)) {
                continue;
            }

            $hours += $this->parseNumber($row[$unitsColumnIndex] ?? null);
            $sum += $this->parseNumber($row[$sumColumnIndex] ?? null);
        }

        return ['hours' => $hours, 'sum' => $sum];
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function findHeaderRowIndex(array $rows): int
    {
        foreach ($rows as $index => $row) {
            $normalized = $this->normalizeRow($row);

            if (in_array('статус оплаты', $normalized, true)) {
                return $index;
            }

            if ($this->rowContainsMonthLabel($row)) {
                return $index;
            }
        }

        return 0;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, string>
     */
    private function normalizeRow(array $row): array
    {
        return array_map(fn ($cell) => $this->normalizeLabel((string) $cell), $row);
    }

    private function normalizeLabel(string $value): string
    {
        $value = trim(mb_strtolower($value));

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    /**
     * @param  array<int, mixed>  $headers
     * @param  array<int, string>  $needles
     */
    private function findColumnIndex(array $headers, array $needles): ?int
    {
        $normalizedHeaders = $this->normalizeRow($headers);

        foreach ($needles as $needle) {
            $needle = $this->normalizeLabel($needle);

            foreach ($normalizedHeaders as $index => $header) {
                if ($header === $needle || str_contains($header, $needle)) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $headers
     */
    private function findMonthColumnIndex(array $headers, Carbon $month): ?int
    {
        $monthNames = $this->monthNameVariants($month);

        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeLabel((string) $header);

            if ($normalized === '') {
                continue;
            }

            foreach ($monthNames as $variant) {
                if ($normalized === $variant || str_contains($normalized, $variant)) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function monthNameVariants(Carbon $month): array
    {
        $full = mb_strtolower($month->locale('ru')->translatedFormat('F'));
        $short = mb_strtolower($month->locale('ru')->translatedFormat('M'));

        return array_values(array_unique(array_filter([
            $full,
            $short,
            $month->format('m.Y'),
            $month->format('m.y'),
            $month->format('Y-m'),
            $month->format('n'),
            $month->format('m'),
        ])));
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function findRowIndexByLabel(array $rows, string $label, int $startIndex = 0): ?int
    {
        $label = $this->normalizeLabel($label);

        for ($i = $startIndex; $i < count($rows); $i++) {
            $firstCell = $this->normalizeLabel((string) ($rows[$i][0] ?? ''));

            if ($firstCell === $label || str_contains($firstCell, $label)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowContainsMonthLabel(array $row): bool
    {
        foreach ($row as $cell) {
            $normalized = $this->normalizeLabel((string) $cell);

            if (preg_match('/^(январ|феврал|март|апрел|ма[йя]|июн|июл|август|сентябр|октябр|ноябр|декабр)/u', $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function rowDateMatchesMonth(mixed $value, Carbon $month): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_numeric($value)) {
            try {
                $serial = (float) $value;
                $base = Carbon::create(1899, 12, 30);
                $date = $base->copy()->addDays((int) floor($serial));

                return $date->year === $month->year && $date->month === $month->month;
            } catch (\Throwable) {
                return false;
            }
        }

        $string = trim((string) $value);

        foreach (['d.m.Y', 'd.m.y', 'Y-m-d', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $string);

                if ($date && $date->year === $month->year && $date->month === $month->month) {
                    return true;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            $date = Carbon::parse($string);

            return $date->year === $month->year && $date->month === $month->month;
        } catch (\Throwable) {
            return false;
        }
    }

    private function parseNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = Str::of((string) $value)
            ->replace([' ', "\xc2\xa0"], '')
            ->replace(',', '.')
            ->replaceMatches('/[^\d.\-]/u', '')
            ->toString();

        if ($normalized === '' || $normalized === '-' || $normalized === '.') {
            return 0.0;
        }

        return (float) $normalized;
    }
}
