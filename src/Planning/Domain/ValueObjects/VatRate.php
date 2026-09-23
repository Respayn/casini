<?php

namespace Src\Planning\Domain\ValueObjects;

/**
 * Ставка НДС для отображения/ввода валютных планов (бюджет, CPL, CPC).
 * В базе всегда хранится сумма без НДС; при включённой галочке на экране × (1 + rate).
 */
final class VatRate
{
    public const PERCENT = 22;

    /** @var list<string> */
    public const AFFECTED_KEYS = ['budget', 'cpl', 'cpc'];

    public static function multiplier(): float
    {
        return 1 + (self::PERCENT / 100);
    }

    public static function affects(string $parameterKey): bool
    {
        return in_array($parameterKey, self::AFFECTED_KEYS, true);
    }

    /**
     * Сырое значение из базы → число для экрана (с НДС при $includeVat).
     */
    public static function toDisplay(?float $netValue, bool $includeVat): ?float
    {
        if ($netValue === null) {
            return null;
        }

        if (! $includeVat) {
            return round($netValue, 2);
        }

        return round($netValue * self::multiplier(), 2);
    }

    /**
     * Число с экрана → значение без НДС для записи в базу.
     */
    public static function toStorage(?float $displayValue, bool $includeVat): ?float
    {
        if ($displayValue === null) {
            return null;
        }

        if (! $includeVat) {
            return round($displayValue, 2);
        }

        return round($displayValue / self::multiplier(), 2);
    }
}
