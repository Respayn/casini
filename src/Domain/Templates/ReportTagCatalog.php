<?php

namespace Src\Domain\Templates;

final class ReportTagCatalog
{
    /**
     * @return list<ReportTag>
     */
    public static function all(): array
    {
        return self::tags();
    }

    /**
     * @return list<ReportTag>
     */
    public static function popular(): array
    {
        return array_values(array_filter(
            self::tags(),
            static fn (ReportTag $tag): bool => $tag->popular,
        ));
    }

    /**
     * @return list<ReportTag>
     */
    private static function tags(): array
    {
        $casini = TemplateTagSource::CASINI->label();
        $callibri = TemplateTagSource::CALLIBRI->label();
        $searchApi = TemplateTagSource::YANDEX_SEARCH_API->label();
        $direct = TemplateTagSource::YANDEX_DIRECT->label();
        $metrika = TemplateTagSource::YANDEX_METRIKA->label();
        $bitrix24 = TemplateTagSource::BITRIX24->label();

        return [
            self::tag('current_year', 'Текущий год', $casini, ReportTagKind::Text, true, 'цифры'),
            self::tag('period_from', 'Начало периода цифрами, например 01.09.2026', $casini, ReportTagKind::Text, true, 'цифры'),
            self::tag('period_to', 'Конец периода цифрами, например 30.09.2026', $casini, ReportTagKind::Text, true, 'цифры'),
            self::tag('period_from_text', 'Начало периода текстом, например 1 сентября 2026', $casini, ReportTagKind::Text, true, 'текст'),
            self::tag('period_to_text', 'Конец периода текстом, например 30 сентября 2026', $casini, ReportTagKind::Text, true, 'текст'),
            self::tag('period_month', 'Отчетный месяц текстом, например сентябрь 2026', $casini, ReportTagKind::Text, true, 'текст'),
            self::tag('project_domain', 'Домен проекта', $casini, ReportTagKind::Text, true),
            self::tag('project_name', 'Название проекта', $casini, ReportTagKind::Text, true),
            self::tag('manager_last_name', 'Фамилия менеджера', $casini, ReportTagKind::Text, true),
            self::tag('manager_first_name', 'Имя менеджера', $casini, ReportTagKind::Text, true),
            self::tag('manager_phone', 'Телефон менеджера', $casini, ReportTagKind::Text, true),
            self::tag('manager_email', 'Почта менеджера', $casini, ReportTagKind::Text, true),
            self::tag('manager_photo', 'Фото менеджера', $casini, ReportTagKind::Image, true),
            self::tag('agency_address', 'Адрес агентства', $casini, ReportTagKind::Text, true),
            self::tag('agency_domain', 'Сайт агентства', $casini, ReportTagKind::Text, true),
            self::tag('agency_image', 'Логотип агентства', $casini, ReportTagKind::Image, true),
            self::tag('agency_email', 'Почта агентства', $casini, ReportTagKind::Text, true),
            self::tag('agency_phone', 'Телефон агентства', $casini, ReportTagKind::Text, true),
            self::tag(
                'yandex_search.region[1]',
                'Название региона. [1] первый в списке настроек интеграции, [2] второй',
                $searchApi,
                ReportTagKind::Text,
            ),
            self::tag(
                'yandex_search.top_3[1]',
                'Доля фраз из этой таблицы в топ-3, например 70%. Знак процента уже в значении, ячейку ставят отдельно от строки с фразой. [1] первый в списке настроек интеграции, [2] второй',
                $searchApi,
                ReportTagKind::Text,
            ),
            self::tag(
                'yandex_search.top_5[1]',
                'Доля фраз из этой таблицы в топ-5, например 70%. Знак процента уже в значении, ячейку ставят отдельно от строки с фразой. [1] первый в списке настроек интеграции, [2] второй',
                $searchApi,
                ReportTagKind::Text,
            ),
            self::tag(
                'yandex_search.top_10[1]',
                'Доля фраз из этой таблицы в топ-10, например 70%. Знак процента уже в значении, ячейку ставят отдельно от строки с фразой. [1] первый в списке настроек интеграции, [2] второй',
                $searchApi,
                ReportTagKind::Text,
            ),
            self::tag(
                'yandex_search.phrase[1]',
                'Поисковая фраза. Ставят в одну строку таблицы Word рядом с позицией. [1] первый в списке настроек интеграции, [2] второй',
                $searchApi,
                ReportTagKind::List,
            ),
            self::tag(
                'yandex_search.position[1]',
                'Позиция этой фразы. Ставят в ту же строку, что и фразу. [1] первый в списке настроек интеграции, [2] второй',
                $searchApi,
                ReportTagKind::List,
            ),
            self::tag('callibri.table', 'Таблица Callibri', $callibri, ReportTagKind::Table),
            self::tag('yd.table', 'Таблица Яндекс.Директ', $direct, ReportTagKind::Table),
            self::tag('yd.table.utm_campaign_match', 'Таблица сопоставления кампаний', $direct, ReportTagKind::Table),
            self::tag('ym.table.goals_search_engines', 'Таблица целей по поисковым системам', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_search_engines.yandex', 'Таблица целей Яндекс', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_search_engines.google', 'Таблица целей Google', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_utm', 'Таблица целей по UTM', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_conversions', 'Таблица конверсий целей', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_direct_summary', 'Таблица целей Директа', $metrika, ReportTagKind::Table),
            self::tag('ym.search_visits.yandex', 'Переходы из Яндекса', $metrika, ReportTagKind::Text, false, 'цифры'),
            self::tag('ym.search_visits.google', 'Переходы из Google', $metrika, ReportTagKind::Text, false, 'цифры'),
            self::tag('ym.search_visits.other', 'Переходы из других поисковых систем', $metrika, ReportTagKind::Text, false, 'цифры'),
            self::tag('ym.search_visits.total', 'Переходы из поисковых систем, итого', $metrika, ReportTagKind::Text, false, 'цифры'),
            self::tag('ym.search_visits.plan', 'План посещаемости из поисковых систем', $metrika, ReportTagKind::Text, false, 'цифры'),
            self::tag('ym.search_visits.fact', 'Факт посещаемости из поисковых систем', $metrika, ReportTagKind::Text, false, 'цифры'),
            self::tag('ym.search_visits.performance', 'Выполнение плана посещаемости, например 70%', $metrika, ReportTagKind::Text, false, 'цифры'),
            self::tag('ym.table.visits_search_systems', 'Таблица визитов из поиска', $metrika, ReportTagKind::Table),
            self::tag('ym.table.visits_geo', 'Таблица визитов по географии', $metrika, ReportTagKind::Table),
            self::tag('ym.table.visits_search_queries', 'Таблица визитов по запросам', $metrika, ReportTagKind::Table),
            self::tag('bitrix24.worklist', 'Закрытые пункты чек-листа задачи месяца и комментарии со спецсимволом 💪', $bitrix24, ReportTagKind::List),
        ];
    }

    private static function tag(
        string $key,
        string $label,
        string $category,
        ReportTagKind $kind,
        bool $popular = false,
        ?string $formatLabel = null,
    ): ReportTag {
        return new ReportTag($key, $label, $category, $kind, $popular, $formatLabel);
    }
}
