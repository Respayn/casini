<?php

namespace Tests\Unit\Enums;

use App\Enums\SearchRegion;
use PHPUnit\Framework\TestCase;

class SearchRegionLabelsTest extends TestCase
{
    public function test_moscow_code_becomes_city_name(): void
    {
        $this->assertSame('Москва', SearchRegion::labelsForCodes([213]));
    }

    public function test_several_regions_are_joined_without_duplicates(): void
    {
        $this->assertSame(
            'Москва, Санкт-Петербург',
            SearchRegion::labelsForCodes([213, 2, '213']),
        );
    }

    public function test_unknown_codes_are_skipped(): void
    {
        $this->assertSame('', SearchRegion::labelsForCodes([]));
        $this->assertSame('', SearchRegion::labelsForCodes(['x', null]));
    }
}
