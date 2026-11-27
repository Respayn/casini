<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MoneyDisplay;
use App\Support\SharedProductFilters;
use Illuminate\Support\Number;
use Tests\TestCase;

class MoneyDisplayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        session()->forget(SharedProductFilters::ROUND_KOPECKS_KEY);
    }

    public function test_rounds_to_whole_rubles_by_default(): void
    {
        $this->assertSame(
            Number::currency(692797.0, in: 'RUB', locale: 'ru', precision: 0),
            MoneyDisplay::currency(692796.77),
        );
    }

    public function test_shows_kopecks_when_rounding_disabled(): void
    {
        SharedProductFilters::rememberRoundKopecks(false);

        $this->assertSame(
            Number::currency(692796.77, in: 'RUB', locale: 'ru', precision: 2),
            MoneyDisplay::currency(692796.77),
        );
    }

    public function test_empty_value_is_dash(): void
    {
        $this->assertSame('-', MoneyDisplay::currency(null));
        $this->assertSame('-', MoneyDisplay::currency(''));
    }
}
