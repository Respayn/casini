<?php

namespace Tests\Unit\Application\Reports;

use PHPUnit\Framework\TestCase;
use Src\Application\Reports\Generate\ReportTemplateLabels;

class ReportTemplateLabelsTest extends TestCase
{
    public function test_indexed_yandex_search_table_uses_base_label(): void
    {
        $this->assertSame(
            'Таблица позиций Яндекс',
            ReportTemplateLabels::label('yandex_search.table[1]'),
        );
        $this->assertSame(
            'Таблица позиций Яндекс',
            ReportTemplateLabels::label('yandex_search.table'),
        );
    }
}
