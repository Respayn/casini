<?php

namespace Tests\Unit\Support;

use App\Exceptions\YandexDirectApiException;
use App\Support\SafeLogger;
use RuntimeException;
use Tests\TestCase;

class SafeLoggerTest extends TestCase
{
    public function test_unwrap_strips_monolog_permission_wrapper(): void
    {
        $message = 'The stream or file "/var/www/casini/storage/logs/laravel-2026-08-16.log" could not be opened in append mode: Failed to open stream: Permission denied'
            ."\nThe exception occurred while attempting to log: Integration sync: Yandex Direct daily spend range failed"
            ."\nContext: {\"project_id\":1,\"message\":\"Failed to get daily expenses\"}";

        $this->assertSame('Failed to get daily expenses', SafeLogger::unwrap($message));
    }

    public function test_unwrap_uses_log_line_when_context_has_no_message(): void
    {
        $message = 'The stream or file "/tmp/x.log" could not be opened in append mode: Failed to open stream: Permission denied'
            ."\nThe exception occurred while attempting to log: Failed to get daily expenses";

        $this->assertSame('Failed to get daily expenses', SafeLogger::unwrap($message));
    }

    public function test_public_message_keeps_plain_errors(): void
    {
        $this->assertSame(
            'Failed to get daily expenses',
            SafeLogger::publicMessage(new RuntimeException('Failed to get daily expenses')),
        );
    }

    public function test_detects_yandex_direct_auth_error_by_code(): void
    {
        $this->assertTrue(SafeLogger::isYandexDirectAuthError(
            new YandexDirectApiException('Invalid request parameters: Недействительный OAuth-токен', 53)
        ));
        $this->assertFalse(SafeLogger::isYandexDirectAuthError(
            new RuntimeException('Failed to get daily expenses')
        ));
    }

    public function test_public_message_prefers_previous_api_error(): void
    {
        $inner = new YandexDirectApiException('Invalid request parameters: Недействительный OAuth-токен', 53);
        $outer = new YandexDirectApiException('Failed to get daily expenses', 0, $inner);

        $this->assertSame(
            'Invalid request parameters: Недействительный OAuth-токен',
            SafeLogger::publicMessage($outer)
        );
        $this->assertTrue(SafeLogger::isYandexDirectAuthError($outer));
    }
}
