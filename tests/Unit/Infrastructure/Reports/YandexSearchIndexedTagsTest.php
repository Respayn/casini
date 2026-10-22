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
            ],
        ], $regions);
        $this->assertSame('yandex_search.region[1]', YandexSearchIndexedTags::key('region', 1));
        $this->assertSame(
            [
                'yandex_search.region[1]',
                'yandex_search.table[1]',
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
        $this->assertSame(2, $regions[1]['index']);
        $this->assertSame(2, $regions[1]['code']);
        $this->assertSame('Санкт-Петербург', $regions[1]['label']);
        $this->assertContains('yandex_search.table[2]', YandexSearchIndexedTags::keysForRegions([
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
            'yandex_search.table[1]',
            'yandex_search.top_3[1]',
            'yandex_search.top_5[1]',
            'yandex_search.top_10[1]',
        ], $keys);

        foreach ($keys as $key) {
            $this->assertDoesNotMatchRegularExpression('/^yandex_search\.(region|table|top_\d+)$/', $key);
            $this->assertMatchesRegularExpression('/^yandex_search\.(region|table|top_3|top_5|top_10)\[\d+\]$/', $key);
        }
    }
}
