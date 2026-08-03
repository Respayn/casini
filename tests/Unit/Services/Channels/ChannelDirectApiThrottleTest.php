<?php

namespace Tests\Unit\Services\Channels;

use App\Services\Channels\ChannelDirectApiThrottle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChannelDirectApiThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_allows_first_request_then_enforces_five_minute_cooldown(): void
    {
        Auth::shouldReceive('id')->andReturn(42);
        $throttle = new ChannelDirectApiThrottle();

        $this->assertTrue($throttle->consume(42)['ok']);

        $second = $throttle->consume(42);
        $this->assertFalse($second['ok']);
        $this->assertStringContainsString('5 минут', (string) $second['error']);
        $this->assertStringContainsString('Подождите ещё 5 мин.', (string) $second['error']);
        $this->assertStringNotContainsString('6 мин', (string) $second['error']);

        Carbon::setTestNow(Carbon::parse('2026-08-03 12:05:00'));
        $this->assertTrue($throttle->consume(42)['ok']);
    }

    public function test_blocks_for_sixty_minutes_after_three_requests(): void
    {
        Auth::shouldReceive('id')->andReturn(7);
        $throttle = new ChannelDirectApiThrottle();

        $this->assertTrue($throttle->consume(7)['ok']);
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:05:00'));
        $this->assertTrue($throttle->consume(7)['ok']);
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:10:00'));
        $this->assertTrue($throttle->consume(7)['ok']);

        Carbon::setTestNow(Carbon::parse('2026-08-03 12:15:00'));
        $blocked = $throttle->consume(7);
        $this->assertFalse($blocked['ok']);
        $this->assertStringContainsString('Лимит запросов', (string) $blocked['error']);

        Carbon::setTestNow(Carbon::parse('2026-08-03 13:15:00'));
        $this->assertTrue($throttle->consume(7)['ok']);
    }
}
