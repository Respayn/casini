<?php

namespace Tests\Unit\Application\Reports;

use PHPUnit\Framework\TestCase;
use Src\Application\Reports\Generate\ReportTemplateLabels;

class ReportTemplateLabelsTest extends TestCase
{
    public function test_indexed_yandex_search_pair_uses_base_label(): void
    {
        $this->assertSame(
            'Фразы Yandex Search API',
            ReportTemplateLabels::label('yandex_search.phrase[1]'),
        );
        $this->assertSame(
            'Позиции Yandex Search API',
            ReportTemplateLabels::label('yandex_search.position[2]'),
        );
        $this->assertSame(
            'Блок «yandex_search.table[1]»',
            ReportTemplateLabels::label('yandex_search.table[1]'),
        );
    }
}
