<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PrimaryParameterPlanHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PrimaryParameterPlanHelperTest extends TestCase
{
    #[DataProvider('suffixProvider')]
    public function test_suffix(?string $code, ?string $expected): void
    {
        $this->assertSame($expected, PrimaryParameterPlanHelper::suffix($code));
    }

    public static function suffixProvider(): array
    {
        return [
            'leads' => ['leads', 'лидов'],
            'visits' => ['visits', 'визитов'],
            'top_percent' => ['top_percent', 'позиций в топ 10'],
            'budget' => ['budget', null],
        ];
    }

    public function test_label_wraps_suffix_in_parentheses(): void
    {
        $this->assertSame('(лидов)', PrimaryParameterPlanHelper::label('leads'));
        $this->assertNull(PrimaryParameterPlanHelper::label('budget'));
    }
}
