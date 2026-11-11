<?php

namespace Tests\Unit\Infrastructure\Reports;

use App\Enums\SearchRegion;
use PHPUnit\Framework\TestCase;
use Src\Infrastructure\Reports\YandexSearchIndexedTags;

class YandexSearchIndexedTagsTest extends TestCase
{
    public function test_one_moscow_region_is_first_index(): void
    {
        $regions = YandexSearchIndexedTags::numberedRegions([
            ['code' => SearchRegion::MOSCOW->value, 'phrases' => ['купить диван']],
        ]);

        $this->assertSame([
            [
                'index' => 1,
                'code' => 213,
                'label' => 'Москва',
                'phrases' => ['купить диван'],
            ],
        ], $regions);
        $this->assertSame('yandex_search.region[1]', YandexSearchIndexedTags::key('region', 1));
        $this->assertSame(
            [
                'yandex_search.region[1]',
                'yandex_search.phrase[1]',
                'yandex_search.position[1]',
                'yandex_search.top_3[1]',
                'yandex_search.top_5[1]',
                'yandex_search.top_10[1]',
            ],
            YandexSearchIndexedTags::keysForRegions([
                ['code' => SearchRegion::MOSCOW->value],
            ]),
        );
    }

    public function test_two_regions_keep_settings_order(): void
    {
        $regions = YandexSearchIndexedTags::numberedRegions([
            ['code' => SearchRegion::MOSCOW->value],
            ['code' => SearchRegion::SAINT_PETERSBURG->value],
        ]);

        $this->assertSame(1, $regions[0]['index']);
        $this->assertSame('Москва', $regions[0]['label']);
        $this->assertSame([], $regions[0]['phrases']);
        $this->assertSame(2, $regions[1]['index']);
        $this->assertSame(2, $regions[1]['code']);
        $this->assertSame('Санкт-Петербург', $regions[1]['label']);
        $this->assertSame([], $regions[1]['phrases']);
        $this->assertContains('yandex_search.phrase[2]', YandexSearchIndexedTags::keysForRegions([
            ['code' => SearchRegion::MOSCOW->value],
            ['code' => SearchRegion::SAINT_PETERSBURG->value],
        ]));
        $this->assertNotContains('yandex_search.region[3]', YandexSearchIndexedTags::keysForRegions([
            ['code' => SearchRegion::MOSCOW->value],
            ['code' => SearchRegion::SAINT_PETERSBURG->value],
        ]));
    }

    public function test_index_beyond_list_is_not_emitted_and_legacy_keys_are_absent(): void
    {
        $keys = YandexSearchIndexedTags::keysForRegions([
            ['code' => 'x'],
            ['code' => SearchRegion::MOSCOW->value],
            ['phrases' => ['фраза']],
        ]);

        $this->assertSame([
            'yandex_search.region[1]',
            'yandex_search.phrase[1]',
            'yandex_search.position[1]',
            'yandex_search.top_3[1]',
            'yandex_search.top_5[1]',
            'yandex_search.top_10[1]',
        ], $keys);

        foreach ($keys as $key) {
            $this->assertDoesNotMatchRegularExpression('/^yandex_search\.(region|table|phrase|position|top_\d+)$/', $key);
            $this->assertMatchesRegularExpression('/^yandex_search\.(region|phrase|position|top_3|top_5|top_10)\[\d+\]$/', $key);
        }
    }

    public function test_top_shares_count_table_rows(): void
    {
        $shares = YandexSearchIndexedTags::topShares([1, 10, 11, '-']);

        $this->assertSame(25.0, $shares['top_3']);
        $this->assertSame(25.0, $shares['top_5']);
        $this->assertSame(50.0, $shares['top_10']);
        $this->assertSame('50%', YandexSearchIndexedTags::formatShare($shares['top_10']));
        $this->assertTrue(YandexSearchIndexedTags::hasNumericRank([1, 10, 11, '-']));
        $this->assertFalse(YandexSearchIndexedTags::hasNumericRank([null, '-', '']));
        $this->assertSame('-', YandexSearchIndexedTags::formatShare(0.0, false));
    }

    public function test_empty_table_gives_zero_share(): void
    {
        $this->assertSame(
            [
                'top_3' => 0.0,
                'top_5' => 0.0,
                'top_10' => 0.0,
            ],
            YandexSearchIndexedTags::topShares([]),
        );
        $this->assertSame('0%', YandexSearchIndexedTags::formatShare(0.0));
        $this->assertFalse(YandexSearchIndexedTags::hasNumericRank([]));
    }

    public function test_empty_tasks_use_settings_phrases_with_empty_position(): void
    {
        $this->assertSame(
            [
                ['phrase' => 'канат гост', 'position' => null],
                ['phrase' => 'метиз', 'position' => null],
            ],
            YandexSearchIndexedTags::keywordRowsOrSettings([], ['канат гост', 'метиз', '']),
        );
        $this->assertSame(
            [['phrase' => 'канат гост', 'position' => 3]],
            YandexSearchIndexedTags::keywordRowsOrSettings(
                [['phrase' => 'канат гост', 'position' => 3]],
                ['метиз'],
            ),
        );
    }
}
