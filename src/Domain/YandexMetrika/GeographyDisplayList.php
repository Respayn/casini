<?php

namespace Src\Domain\YandexMetrika;

/**
 * Измерения API для отчёта «География» (preset geo_country).
 */
class GeographyDisplayList
{
    /**
     * Город — лист дерева «География» (country → area → city).
     */
    public const CITY_DIMENSION = 'ym:s:regionCity';
}
