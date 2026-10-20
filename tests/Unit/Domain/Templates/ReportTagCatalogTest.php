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
        $this->assertSame(ReportTagKind::List, $tags['megaplan.list']->kind);
        $this->assertSame('текст', $tags['project_name']->parameterLabel());
        $this->assertSame('изображение', $tags['manager_photo']->parameterLabel());
        $this->assertSame('таблица', $tags['yd.table']->parameterLabel());
        $this->assertSame('список, строка на каждую работу', $tags['megaplan.list']->parameterLabel());
    }

    public function test_every_tag_has_display_format(): void
    {
        foreach (ReportTagCatalog::all() as $tag) {
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
