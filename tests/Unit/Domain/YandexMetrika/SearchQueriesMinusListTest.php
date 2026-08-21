<?php

namespace Tests\Unit\Domain\YandexMetrika;

use PHPUnit\Framework\Attributes\Test;
use Src\Domain\YandexMetrika\SearchQueriesMinusList;
use Tests\TestCase;

class SearchQueriesMinusListTest extends TestCase
{
    #[Test]
    public function test_parse_splits_lines_and_trims(): void
    {
        $list = SearchQueriesMinusList::parse("Вакансии\n Реквизиты \n\nбренд\n");

        $this->assertSame(['Вакансии', 'Реквизиты', 'бренд'], $list);
    }

    #[Test]
    public function test_build_filter_returns_null_for_empty(): void
    {
        $this->assertNull(SearchQueriesMinusList::buildFilter(''));
        $this->assertNull(SearchQueriesMinusList::buildFilter("  \n  "));
    }

    #[Test]
    public function test_build_filter_for_single_phrase(): void
    {
        $this->assertSame(
            "ym:s:<attribution>SearchPhrase!@'Вакансии'",
            SearchQueriesMinusList::buildFilter('Вакансии')
        );
    }

    #[Test]
    public function test_build_filter_for_multiple_phrases_uses_and(): void
    {
        $this->assertSame(
            "(ym:s:<attribution>SearchPhrase!@'Вакансии' AND ym:s:<attribution>SearchPhrase!@'Реквизиты')",
            SearchQueriesMinusList::buildFilter("Вакансии\nРеквизиты")
        );
    }

    #[Test]
    public function test_build_filter_escapes_quotes(): void
    {
        $this->assertSame(
            "ym:s:<attribution>SearchPhrase!@'O\\'Reilly'",
            SearchQueriesMinusList::buildFilter("O'Reilly")
        );
    }
}
