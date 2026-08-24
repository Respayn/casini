<?php

namespace Tests\Unit\Domain\YandexMetrika;

use PHPUnit\Framework\Attributes\Test;
use Src\Domain\YandexMetrika\YandexMetrikaFiltersBuilder;
use Tests\TestCase;

class YandexMetrikaFiltersBuilderTest extends TestCase
{
    private YandexMetrikaFiltersBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new YandexMetrikaFiltersBuilder();
    }

    #[Test]
    public function test_empty_fields_with_robots_return_null(): void
    {
        $this->assertNull($this->builder->build(null, YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS));
        $this->assertNull($this->builder->build([
            'entry_page' => '',
            'last_search_phrase' => null,
            'geo' => "  \n  ",
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS));
    }

    #[Test]
    public function test_affirmative_entry_pages_join_with_or(): void
    {
        $filters = $this->builder->build([
            'entry_page' => "catalog\nstore",
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame(
            "(ym:s:startURL=@'catalog' OR ym:s:startURL=@'store')",
            $filters
        );
    }

    #[Test]
    public function test_negated_absolute_entry_url_uses_not_equals(): void
    {
        $filters = $this->builder->build([
            'entry_page' => '!https://mmk-metiz.ru/',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame("ym:s:startURL!='https://mmk-metiz.ru/'", $filters);
    }

    #[Test]
    public function test_absolute_entry_url_without_negation_uses_equals(): void
    {
        $filters = $this->builder->build([
            'entry_page' => 'https://mmk-metiz.ru/catalog',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame("ym:s:startURL=='https://mmk-metiz.ru/catalog'", $filters);
    }

    #[Test]
    public function test_negated_relative_entry_page_still_uses_not_contains(): void
    {
        $filters = $this->builder->build([
            'entry_page' => '!catalog',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame("ym:s:startURL!@'catalog'", $filters);
    }

    #[Test]
    public function test_negated_wildcard_uses_not_match_operator(): void
    {
        $filters = $this->builder->build([
            'entry_page' => '!*promo*',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame("ym:s:startURL!*'*promo*'", $filters);
    }

    #[Test]
    public function test_mixed_affirmative_and_negative_lines_in_one_field(): void
    {
        $filters = $this->builder->build([
            'entry_page' => "catalog\nstore\n!*promo*",
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame(
            "(ym:s:startURL=@'catalog' OR ym:s:startURL=@'store') AND ym:s:startURL!*'*promo*'",
            $filters
        );
    }

    #[Test]
    public function test_three_fields_join_with_and(): void
    {
        $filters = $this->builder->build([
            'entry_page' => 'catalog',
            'last_search_phrase' => 'кейс',
            'geo' => 'Москва',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame(
            "ym:s:startURL=@'catalog'"
            . " AND ym:s:<attribution>SearchPhrase=='кейс'"
            . " AND (ym:s:regionCityName=@'Москва' OR ym:s:regionCountryName=@'Москва' OR ym:s:regionAreaName=@'Москва')",
            $filters
        );
    }

    #[Test]
    public function test_without_robots_adds_is_robot_filter(): void
    {
        $filters = $this->builder->build(null, YandexMetrikaFiltersBuilder::DATA_MODE_WITHOUT_ROBOTS);

        $this->assertSame("ym:s:isRobot=='No'", $filters);
    }

    #[Test]
    public function test_without_robots_and_entry_page_join_with_and(): void
    {
        $filters = $this->builder->build([
            'entry_page' => 'catalog',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITHOUT_ROBOTS);

        $this->assertSame(
            "ym:s:isRobot=='No' AND ym:s:startURL=@'catalog'",
            $filters
        );
    }

    #[Test]
    public function test_with_robots_does_not_add_is_robot_filter(): void
    {
        $filters = $this->builder->build([
            'entry_page' => 'catalog',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame("ym:s:startURL=@'catalog'", $filters);
        $this->assertStringNotContainsString('isRobot', $filters);
    }

    #[Test]
    public function test_escapes_quotes_and_backslashes_in_values(): void
    {
        $filters = $this->builder->build([
            'last_search_phrase' => "foo'bar\\baz",
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame("ym:s:<attribution>SearchPhrase=='foo\\'bar\\\\baz'", $filters);
    }

    #[Test]
    public function test_skips_empty_and_bang_only_lines(): void
    {
        $filters = $this->builder->build([
            'entry_page' => "catalog\n\n!\n  \nstore",
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame(
            "(ym:s:startURL=@'catalog' OR ym:s:startURL=@'store')",
            $filters
        );
    }

    #[Test]
    public function test_wildcard_without_negation_uses_match_operator(): void
    {
        $filters = $this->builder->build([
            'last_search_phrase' => '*кейс*',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame('ym:s:<attribution>SearchPhrase=*' . "'*кейс*'", $filters);
    }

    #[Test]
    public function test_negated_search_phrase_without_wildcard_uses_not_equals(): void
    {
        $filters = $this->builder->build([
            'last_search_phrase' => '!ммк метиз',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame("ym:s:<attribution>SearchPhrase!='ммк метиз'", $filters);
    }

    #[Test]
    public function test_negated_geo_joins_dimensions_with_and(): void
    {
        $filters = $this->builder->build([
            'geo' => '!*Москва*',
        ], YandexMetrikaFiltersBuilder::DATA_MODE_WITH_ROBOTS);

        $this->assertSame(
            "(ym:s:regionCityName!*'*Москва*' AND ym:s:regionCountryName!*'*Москва*' AND ym:s:regionAreaName!*'*Москва*')",
            $filters
        );
    }
}
