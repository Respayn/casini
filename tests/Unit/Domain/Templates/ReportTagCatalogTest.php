<?php

namespace Tests\Unit\Domain\Templates;

use PHPUnit\Framework\TestCase;
use Src\Domain\Templates\ReportTag;
use Src\Domain\Templates\ReportTagCatalog;
use Src\Domain\Templates\ReportTagKind;

class ReportTagCatalogTest extends TestCase
{
    public function test_keys_are_unique_and_not_empty(): void
    {
        $keys = [];

        foreach (ReportTagCatalog::all() as $tag) {
            $this->assertNotSame('', $tag->key);
            $this->assertArrayNotHasKey($tag->key, $keys);
            $keys[$tag->key] = true;
        }

        $this->assertNotEmpty($keys);
    }

    public function test_popular_tags_are_part_of_full_list(): void
    {
        $keys = array_map(
            static fn ($tag): string => $tag->key,
            ReportTagCatalog::all(),
        );

        foreach (ReportTagCatalog::popular() as $tag) {
            $this->assertContains($tag->key, $keys);
            $this->assertTrue($tag->popular);
        }
    }

    public function test_kinds_match_insert_type(): void
    {
        $tags = $this->tagsByKey();

        $this->assertSame(ReportTagKind::Image, $tags['manager_photo']->kind);
        $this->assertSame(ReportTagKind::Image, $tags['agency_image']->kind);
        $this->assertSame(ReportTagKind::Table, $tags['yd.table']->kind);
        $this->assertSame(ReportTagKind::Table, $tags['callibri.table']->kind);
        $this->assertArrayNotHasKey('megaplan.list', $tags);
        $this->assertSame(ReportTagKind::Text, $tags['period_from']->kind);
        $this->assertSame('Начало периода цифрами, например 01.09.2026', $tags['period_from']->label);
        $this->assertSame('{{period_from}}', $tags['period_from']->placeholder());
        $this->assertSame(ReportTagKind::Text, $tags['period_to']->kind);
        $this->assertSame('Конец периода цифрами, например 30.09.2026', $tags['period_to']->label);
        $this->assertSame('{{period_to}}', $tags['period_to']->placeholder());
        $this->assertSame(ReportTagKind::Text, $tags['period_month']->kind);
        $this->assertSame('Отчетный месяц текстом, например сентябрь 2026', $tags['period_month']->label);
        $this->assertSame('{{period_month}}', $tags['period_month']->placeholder());
        $this->assertArrayNotHasKey('yandex_search.region', $tags);
        $this->assertArrayNotHasKey('yandex_search.table', $tags);
        $this->assertArrayNotHasKey('yandex_search.top_3', $tags);
        $this->assertArrayNotHasKey('yandex_search.top_5', $tags);
        $this->assertArrayNotHasKey('yandex_search.top_10', $tags);
        $this->assertSame(ReportTagKind::Text, $tags['yandex_search.region[1]']->kind);
        $this->assertSame(
            'Название региона. [1] первый в списке настроек интеграции, [2] второй',
            $tags['yandex_search.region[1]']->label,
        );
        $this->assertSame('{{yandex_search.region[1]}}', $tags['yandex_search.region[1]']->placeholder());
        $this->assertSame(ReportTagKind::Table, $tags['yandex_search.table[1]']->kind);
        $this->assertSame(
            'Таблица «Фраза / Позиция» только фраз этого региона. [1] первый в списке настроек интеграции, [2] второй',
            $tags['yandex_search.table[1]']->label,
        );
        $this->assertSame('{{yandex_search.table[1]}}', $tags['yandex_search.table[1]']->placeholder());
        foreach ([
            'yandex_search.region[1]',
            'yandex_search.table[1]',
            'yandex_search.top_3[1]',
            'yandex_search.top_5[1]',
            'yandex_search.top_10[1]',
        ] as $searchApiKey) {
            $this->assertStringContainsString('[1]', $tags[$searchApiKey]->key);
            $this->assertStringContainsString('[1] первый в списке настроек интеграции, [2] второй', $tags[$searchApiKey]->label);
        }
        $this->assertSame(
            'Доля фраз этого региона в топ-3. [1] первый в списке настроек интеграции, [2] второй',
            $tags['yandex_search.top_3[1]']->label,
        );
        $this->assertSame(
            'Доля фраз этого региона в топ-5. [1] первый в списке настроек интеграции, [2] второй',
            $tags['yandex_search.top_5[1]']->label,
        );
        $this->assertSame(
            'Доля фраз этого региона в топ-10. [1] первый в списке настроек интеграции, [2] второй',
            $tags['yandex_search.top_10[1]']->label,
        );
        $this->assertSame(ReportTagKind::List, $tags['bitrix24.worklist']->kind);
        $this->assertSame(
            'Закрытые пункты чек-листа задачи месяца и комментарии со спецсимволом 💪',
            $tags['bitrix24.worklist']->label,
        );
        $this->assertSame('текст', $tags['project_name']->parameterLabel());
        $this->assertSame('изображение', $tags['manager_photo']->parameterLabel());
        $this->assertSame('таблица', $tags['yd.table']->parameterLabel());
        $this->assertSame('список', $tags['bitrix24.worklist']->parameterLabel());
    }

    public function test_every_tag_has_display_format(): void
    {
        foreach (ReportTagCatalog::all() as $tag) {
            $this->assertNotSame('', trim($tag->label));
            $this->assertNotSame('', trim($tag->parameterLabel()));
            $this->assertNotSame('-', $tag->parameterLabel());
        }
    }

    public function test_catalog_has_no_legacy_tag_parameters(): void
    {
        foreach (ReportTagCatalog::all() as $tag) {
            $haystack = $tag->key.' '.$tag->label.' '.$tag->parameterLabel();

            $this->assertStringNotContainsString('ссылка=да', $haystack);
            $this->assertDoesNotMatchRegularExpression('/изображение=\d/u', $haystack);
        }
    }

    /**
     * @return array<string, ReportTag>
     */
    private function tagsByKey(): array
    {
        $tags = [];

        foreach (ReportTagCatalog::all() as $tag) {
            $tags[$tag->key] = $tag;
        }

        return $tags;
    }
}
