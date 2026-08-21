<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\YandexMetrika\YandexMetrikaUtmFilterBuilder;

class YandexMetrikaUtmFilterBuilderTest extends TestCase
{
    private YandexMetrikaUtmFilterBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new YandexMetrikaUtmFilterBuilder();
    }

    public function test_empty_value_produces_not_empty_filter(): void
    {
        $this->assertSame("ym:s:<attribution>UTMSource!=''", $this->builder->build('source'));
        $this->assertSame("ym:s:<attribution>UTMMedium!=''", $this->builder->build('medium', ''));
        $this->assertSame("ym:s:<attribution>UTMCampaign!=''", $this->builder->build('campaign', '  '));
    }

    public function test_single_value_produces_contains(): void
    {
        $this->assertSame("ym:s:<attribution>UTMSource=@'yandex'", $this->builder->build('source', 'yandex'));
    }

    public function test_wildcard_value_produces_pattern_match(): void
    {
        $this->assertSame("ym:s:<attribution>UTMSource=*'*yandex*'", $this->builder->build('source', '*yandex*'));
    }

    public function test_multiple_values_joined_with_or(): void
    {
        $result = $this->builder->build('medium', 'cpc, organic');
        $this->assertSame("(ym:s:<attribution>UTMMedium=@'cpc' OR ym:s:<attribution>UTMMedium=@'organic')", $result);
    }

    public function test_unknown_mode_returns_null(): void
    {
        $this->assertNull($this->builder->build('invalid'));
        $this->assertNull($this->builder->dimension('invalid'));
    }

    public function test_dimension_matches_filter_dimension(): void
    {
        $this->assertSame('ym:s:<attribution>UTMSource', $this->builder->dimension('source'));
        $this->assertSame('ym:s:<attribution>UTMMedium', $this->builder->dimension('medium'));
        $this->assertSame('ym:s:<attribution>UTMCampaign', $this->builder->dimension('campaign'));
    }

    public function test_escapes_quotes(): void
    {
        $result = $this->builder->build('source', "it's");
        $this->assertSame("ym:s:<attribution>UTMSource=@'it\\'s'", $result);
    }
}
