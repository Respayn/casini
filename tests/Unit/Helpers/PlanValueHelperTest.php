<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PlanValueHelper;
use Illuminate\Support\Number;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlanValueHelperTest extends TestCase
{
    #[DataProvider('planValueProvider')]
    public function test_formats_plan_values_with_rounding(mixed $value, ?string $format, string $expected): void
    {
        $this->assertSame($expected, PlanValueHelper::format($value, $format));
    }

    public static function planValueProvider(): array
    {
        return [
            'long float default' => [3333.3333333333, null, Number::format(3333.0, precision: 0, locale: 'ru')],
            'integer default' => [5130.0, null, Number::format(5130.0, precision: 0, locale: 'ru')],
            'percent' => [50.333333, 'percent', Number::format(50.0, precision: 0, locale: 'ru').'%'],
            'currency integer' => [10000.0, 'currency', Number::currency(10000.0, in: 'RUB', locale: 'ru', precision: 0)],
            'empty' => [null, null, '-'],
        ];
    }
}
