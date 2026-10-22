<?php

namespace Src\Application\Reports\Generate;

class ReportTemplateLabels
{
    private const LABELS = [
        'agency_image' => 'Логотип агентства',
        'manager_photo' => 'Фото менеджера',
        'callibri.table' => 'Таблица Callibri',
        'yandex_search.phrase' => 'Фразы Yandex Search API',
        'yandex_search.position' => 'Позиции Yandex Search API',
        'yd.table' => 'Таблица Яндекс.Директ',
        'yd.table.utm_campaign_match' => 'Таблица сопоставления кампаний',
        'ym.table.goals_search_engines' => 'Таблица целей по поисковым системам',
        'ym.table.goals_search_engines.yandex' => 'Таблица целей Яндекс',
        'ym.table.goals_search_engines.google' => 'Таблица целей Google',
        'ym.table.goals_utm' => 'Таблица целей по UTM',
        'ym.table.goals_conversions' => 'Таблица конверсий целей',
        'ym.table.goals_direct_summary' => 'Таблица целей Директа',
        'ym.table.visits_search_systems' => 'Таблица визитов из поиска',
        'ym.table.visits_geo' => 'Таблица визитов по географии',
        'ym.table.visits_search_queries' => 'Таблица визитов по запросам',
        'bitrix24.worklist' => 'Список выполненных работ',
    ];

    public static function label(string $key): string
    {
        $normalized = preg_replace('/\[\d+\]$/', '', $key) ?? $key;

        return self::LABELS[$normalized] ?? 'Блок «'.$key.'»';
    }
}
