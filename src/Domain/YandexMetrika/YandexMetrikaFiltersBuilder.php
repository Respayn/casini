<?php

namespace Src\Domain\YandexMetrika;

class YandexMetrikaFiltersBuilder
{
    public const DATA_MODE_WITHOUT_ROBOTS = 'without_robots';

    public const DATA_MODE_WITH_ROBOTS = 'with_robots';

    private const DIMENSION_ENTRY_PAGE = 'ym:s:startURL';

    private const DIMENSION_SEARCH_PHRASE = 'ym:s:lastsignSearchPhrase';

    /**
     * @var list<string>
     */
    private const GEO_DIMENSIONS = [
        'ym:s:regionCityName',
        'ym:s:regionCountryName',
        'ym:s:regionAreaName',
    ];

    /**
     * Собирает параметр filters для Reporting API Метрики.
     *
     * Пустое поле пропускается. Утверждения внутри поля соединяются через OR,
     * отрицания (строка начинается с «!») — через AND. Разные поля и фильтр
     * «без роботов» соединяются через AND.
     *
     * @param array{entry_page?: ?string, last_search_phrase?: ?string, geo?: ?string}|null $filters
     */
    public function build(?array $filters, string $dataMode = self::DATA_MODE_WITHOUT_ROBOTS): ?string
    {
        $parts = [];

        if ($dataMode !== self::DATA_MODE_WITH_ROBOTS) {
            $parts[] = "ym:s:isRobot=='No'";
        }

        $filters = $filters ?? [];
        $entryPage = $this->buildFieldExpression(self::DIMENSION_ENTRY_PAGE, $filters['entry_page'] ?? null);
        if ($entryPage !== null) {
            $parts[] = $entryPage;
        }

        $searchPhrase = $this->buildFieldExpression(self::DIMENSION_SEARCH_PHRASE, $filters['last_search_phrase'] ?? null);
        if ($searchPhrase !== null) {
            $parts[] = $searchPhrase;
        }

        $geo = $this->buildGeoExpression($filters['geo'] ?? null);
        if ($geo !== null) {
            $parts[] = $geo;
        }

        if ($parts === []) {
            return null;
        }

        return implode(' AND ', $parts);
    }

    private function buildFieldExpression(string $dimension, ?string $raw): ?string
    {
        $parsed = $this->parseLines($raw);
        if ($parsed === []) {
            return null;
        }

        return $this->combineLines(
            $parsed,
            fn (string $value, bool $negated): string => $this->condition($dimension, $value, $negated)
        );
    }

    private function buildGeoExpression(?string $raw): ?string
    {
        $parsed = $this->parseLines($raw);
        if ($parsed === []) {
            return null;
        }

        return $this->combineLines($parsed, function (string $value, bool $negated): string {
            $conditions = [];
            foreach (self::GEO_DIMENSIONS as $dimension) {
                $conditions[] = $this->condition($dimension, $value, $negated);
            }

            $joiner = $negated ? ' AND ' : ' OR ';

            return '(' . implode($joiner, $conditions) . ')';
        });
    }

    /**
     * @param list<array{value: string, negated: bool}> $lines
     * @param callable(string, bool): string $toCondition
     */
    private function combineLines(array $lines, callable $toCondition): string
    {
        $positives = [];
        $negatives = [];

        foreach ($lines as $line) {
            $condition = $toCondition($line['value'], $line['negated']);
            if ($line['negated']) {
                $negatives[] = $condition;
            } else {
                $positives[] = $condition;
            }
        }

        $parts = [];
        if ($positives !== []) {
            $parts[] = count($positives) === 1
                ? $positives[0]
                : '(' . implode(' OR ', $positives) . ')';
        }

        foreach ($negatives as $negative) {
            $parts[] = $negative;
        }

        return implode(' AND ', $parts);
    }

    /**
     * @return list<array{value: string, negated: bool}>
     */
    private function parseLines(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $lines = [];
        foreach (preg_split("/\r\n|\n|\r/", $raw) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $negated = str_starts_with($trimmed, '!');
            $value = $negated ? trim(substr($trimmed, 1)) : $trimmed;
            if ($value === '') {
                continue;
            }

            $lines[] = [
                'value' => $value,
                'negated' => $negated,
            ];
        }

        return $lines;
    }

    private function condition(string $dimension, string $value, bool $negated): string
    {
        $hasWildcard = str_contains($value, '*');
        // Полный URL страницы входа в Метрике — точное сравнение (== / !=),
        // а не «содержит»: иначе !https://site/ исключает весь домен → 0.
        $isAbsoluteEntryUrl = $dimension === self::DIMENSION_ENTRY_PAGE
            && ! $hasWildcard
            && preg_match('#^https?://#i', $value) === 1;

        if ($isAbsoluteEntryUrl) {
            $operator = $negated ? '!=' : '==';
        } elseif ($negated) {
            $operator = $hasWildcard ? '!*' : '!@';
        } else {
            $operator = $hasWildcard ? '=*' : '=@';
        }

        return $dimension.$operator."'".$this->escapeValue($value)."'";
    }

    private function escapeValue(string $value): string
    {
        return addcslashes($value, "\\'");
    }
}
