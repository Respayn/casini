<?php

namespace Tests\Unit\Services\Bitrix24;

use App\Services\Bitrix24\Bitrix24WorkParser;
use PHPUnit\Framework\TestCase;

class Bitrix24WorkParserTest extends TestCase
{
    public function test_checklist_complete_flag_accepts_bitrix_values(): void
    {
        $this->assertTrue(Bitrix24WorkParser::isChecklistComplete('Y'));
        $this->assertTrue(Bitrix24WorkParser::isChecklistComplete(true));
        $this->assertTrue(Bitrix24WorkParser::isChecklistComplete(1));
        $this->assertFalse(Bitrix24WorkParser::isChecklistComplete('N'));
        $this->assertFalse(Bitrix24WorkParser::isChecklistComplete(false));
        $this->assertFalse(Bitrix24WorkParser::isChecklistComplete(0));
    }

    public function test_comment_without_marker_is_ignored(): void
    {
        $this->assertSame([], Bitrix24WorkParser::fromComment('Обычный комментарий'));
        $this->assertSame([], Bitrix24WorkParser::fromComment(''));
    }

    public function test_items_after_marker_are_collected(): void
    {
        $this->assertSame(
            ['написать тексты', 'сдать отчёт'],
            Bitrix24WorkParser::fromComment('💪 написать тексты 💪 сдать отчёт'),
        );
    }

    public function test_html_and_spaces_are_normalized(): void
    {
        $this->assertSame(
            ['правки мета-тегов'],
            Bitrix24WorkParser::fromComment('<p>💪&nbsp;правки   мета-тегов</p>'),
        );
    }
}
