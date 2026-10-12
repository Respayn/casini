<?php

namespace Tests\Unit\Domain\YandexMetrika;

use PHPUnit\Framework\Attributes\Test;
use Src\Domain\YandexMetrika\SearchEnginesDisplayList;
use Tests\TestCase;

class SearchEnginesDisplayListTest extends TestCase
{
    #[Test]
    public function test_migrate_display_text_to_root_ids(): void
    {
        $ids = SearchEnginesDisplayList::migrateDisplayTextToIds("Яндекс\n Google \n\nBing\nUnknown Engine\nЯндекс\n");

        $this->assertSame(['yandex', 'google', 'bing'], $ids);
    }

    #[Test]
    public function test_migrate_matches_api_english_variants(): void
    {
        $ids = SearchEnginesDisplayList::migrateDisplayTextToIds("Yandex, search results\nYandex Mobile\n");

        $this->assertSame(['yandex'], $ids);
    }

    #[Test]
    public function test_build_filter_returns_null_for_all_mode(): void
    {
        $this->assertNull(SearchEnginesDisplayList::buildSearchEngineRootFilter(true, ['yandex']));
    }

    #[Test]
    public function test_build_filter_for_single_id(): void
    {
        $this->assertSame(
            "ym:s:<attribution>SearchEngineRoot=@'yandex'",
            SearchEnginesDisplayList::buildSearchEngineRootFilter(false, ['yandex'])
        );
    }

    #[Test]
    public function test_build_filter_for_multiple_ids(): void
    {
        $this->assertSame(
            "(ym:s:<attribution>SearchEngineRoot=@'yandex' OR ym:s:<attribution>SearchEngineRoot=@'google')",
            SearchEnginesDisplayList::buildSearchEngineRootFilter(false, ['yandex', 'google'])
        );
    }

    #[Test]
    public function test_build_filter_returns_null_for_empty_ids(): void
    {
        $this->assertNull(SearchEnginesDisplayList::buildSearchEngineRootFilter(false, []));
    }
}
