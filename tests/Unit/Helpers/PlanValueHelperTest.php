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

    #[DataProvider('planColumnPartsProvider')]
    public function test_plan_column_parts_for_primary_parameters(
        mixed $value,
        ?string $format,
        ?string $code,
        bool $showPrimarySuffix,
        array $expected,
    ): void {
        $this->assertSame($expected, PlanValueHelper::planColumnParts($value, $format, $code, $showPrimarySuffix));
    }

    public static function planColumnPartsProvider(): array
    {
        return [
            'primary leads' => [56, null, 'leads', true, ['value' => '56', 'suffix' => '(лидов)']],
            'primary visits' => [5130, null, 'visits', true, ['value' => Number::format(5130.0, precision: 0, locale: 'ru'), 'suffix' => '(визитов)']],
            'primary top_percent' => [50, 'percent', 'top_percent', true, ['value' => '50%', 'suffix' => '(позиций в топ 10)']],
            'non-primary budget' => [10000, 'currency', 'budget', false, ['value' => Number::currency(10000.0, in: 'RUB', locale: 'ru', precision: 0), 'suffix' => null]],
        ];
    }
}
