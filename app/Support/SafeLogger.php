<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Логирование, которое не подменяет исходную ошибку съёма, если файл лога недоступен.
 */
class SafeLogger
{
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /**
     * Текст для last_error / UI: без обёртки Monolog про права на файл лога.
     */
    public static function publicMessage(Throwable $e): string
    {
        $unwrapped = self::unwrap($e->getMessage());
        $previous = $e->getPrevious();

        if ($previous instanceof Throwable && self::isGenericExpenseMessage($unwrapped)) {
            return self::publicMessage($previous);
        }

        return $unwrapped;
    }

    public static function unwrap(string $message): string
    {
        $current = $message;

        for ($i = 0; $i < 5; $i++) {
            $next = null;

            if (preg_match('/\nContext:\s*(\{.*\})\s*$/s', $current, $ctx) === 1) {
                $decoded = json_decode($ctx[1], true);
                if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message']) && $decoded['message'] !== '') {
                    $next = $decoded['message'];
                }
            }

            if ($next === null && str_contains($current, 'The exception occurred while attempting to log:')) {
                $after = trim((string) strstr($current, 'The exception occurred while attempting to log:'));
                $after = trim(substr($after, strlen('The exception occurred while attempting to log:')));
                $after = trim(preg_split("/\nContext:/", $after, 2)[0] ?? $after);

                if ($after !== '' && $after !== $current) {
                    $next = $after;
                }
            }

            if ($next === null || $next === $current) {
                break;
            }

            $current = $next;
        }

        return $current;
    }

    public static function isYandexDirectAuthError(Throwable $e): bool
    {
        $haystack = $e->getMessage();
        $previous = $e->getPrevious();
        if ($previous instanceof Throwable) {
            $haystack .= ' '.$previous->getMessage();
        }

        if ((int) $e->getCode() === 53) {
            return true;
        }

        if ($previous instanceof Throwable && (int) $previous->getCode() === 53) {
            return true;
        }

        return str_contains($haystack, 'error_code":"53')
            || str_contains($haystack, 'Недействительный OAuth')
            || str_contains($haystack, 'Authentication failed')
            || str_contains($haystack, 'invalid_grant')
            || str_contains($haystack, 'expired_token');
    }

    private static function isGenericExpenseMessage(string $message): bool
    {
        return in_array($message, [
            'Failed to get daily expenses',
            'Failed to get monthly expenses',
        ], true);
    }

    private static function write(string $level, string $message, array $context): void
    {
        try {
            Log::{$level}($message, $context);
        } catch (Throwable) {
            // Права на лог не должны ронять съём и подменять last_error.
        }
    }
}
