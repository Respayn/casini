<?php

namespace Tests\Unit\Domain\Templates;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Domain\Templates\TemplateTagSource;

class TemplateTagSourceTest extends TestCase
{
    #[DataProvider('tags')]
    public function test_source_is_detected_by_tag_prefix(string $tag, TemplateTagSource $expected): void
    {
        $this->assertSame($expected, TemplateTagSource::fromTag($tag));
    }

    public static function tags(): array
    {
        return [
            ['current_year', TemplateTagSource::CASINI],
            ['period_from', TemplateTagSource::CASINI],
            ['manager_photo', TemplateTagSource::CASINI],
            ['agency_phone', TemplateTagSource::CASINI],
            ['callibri.table', TemplateTagSource::CALLIBRI],
            ['yandex_search.region[1]', TemplateTagSource::YANDEX_SEARCH_API],
            ['yandex_search.phrase[1]', TemplateTagSource::YANDEX_SEARCH_API],
            ['yandex_search.position[2]', TemplateTagSource::YANDEX_SEARCH_API],
            ['yandex_search.top_3[1]', TemplateTagSource::YANDEX_SEARCH_API],
            ['yandex_search.region', TemplateTagSource::YANDEX_SEARCH_API],
            ['yd.table.utm_campaign_match', TemplateTagSource::YANDEX_DIRECT],
            ['ym.table.visits_geo', TemplateTagSource::YANDEX_METRIKA],
            ['ym.search_visits.yandex', TemplateTagSource::YANDEX_METRIKA],
            ['ym.search_queries.phrase', TemplateTagSource::YANDEX_METRIKA],
            ['bitrix24.worklist', TemplateTagSource::BITRIX24],
            ['client_name', TemplateTagSource::UNKNOWN],
        ];
    }
}
