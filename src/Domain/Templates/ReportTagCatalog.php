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
        $works = 'Выполненные работы';

        return [
            self::tag('current_year', 'Текущий год', $casini, ReportTagKind::Text, true),
            self::tag('period_from', 'Начало периода', $casini, ReportTagKind::Text, true),
            self::tag('period_to', 'Конец периода', $casini, ReportTagKind::Text, true),
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
            self::tag('yandex_search.top_3', 'Доля фраз в топ-3', $searchApi, ReportTagKind::Text),
            self::tag('yandex_search.top_5', 'Доля фраз в топ-5', $searchApi, ReportTagKind::Text),
            self::tag('yandex_search.top_10', 'Доля фраз в топ-10', $searchApi, ReportTagKind::Text),
            self::tag('yandex_search.table', 'Таблица позиций Яндекс', $searchApi, ReportTagKind::Table),
            self::tag('callibri.table', 'Таблица Callibri', $callibri, ReportTagKind::Table),
            self::tag('yd.table', 'Таблица Яндекс.Директ', $direct, ReportTagKind::Table),
            self::tag('yd.table.utm_campaign_match', 'Таблица сопоставления кампаний', $direct, ReportTagKind::Table),
            self::tag('ym.table.goals_search_engines', 'Таблица целей по поисковым системам', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_search_engines.yandex', 'Таблица целей Яндекс', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_search_engines.google', 'Таблица целей Google', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_utm', 'Таблица целей по UTM', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_conversions', 'Таблица конверсий целей', $metrika, ReportTagKind::Table),
            self::tag('ym.table.goals_direct_summary', 'Таблица целей Директа', $metrika, ReportTagKind::Table),
            self::tag('ym.table.visits_search_systems', 'Таблица визитов из поиска', $metrika, ReportTagKind::Table),
            self::tag('ym.table.visits_geo', 'Таблица визитов по географии', $metrika, ReportTagKind::Table),
            self::tag('ym.table.visits_search_queries', 'Таблица визитов по запросам', $metrika, ReportTagKind::Table),
            self::tag('megaplan.list', 'Список выполненных работ', $works, ReportTagKind::List),
        ];
    }

    private static function tag(
        string $key,
        string $label,
        string $category,
        ReportTagKind $kind,
        bool $popular = false,
    ): ReportTag {
        return new ReportTag($key, $label, $category, $kind, $popular);
    }
}
