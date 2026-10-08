<?php

namespace App\Support;

final class Bitrix24ProjectSettingsValidator
{
    public const ROOT_TASK_URL_ERROR = 'Введите корректный URL задачи Битрикс24';

    public const ROOT_TASK_REQUIRED_ERROR = 'Укажите URL корневой задачи';

    public static function isValidRootTaskUrl(string $rootTask): bool
    {
        $rootTask = trim($rootTask);

        if ($rootTask === '') {
            return false;
        }

        return filter_var($rootTask, FILTER_VALIDATE_URL) !== false
            && (str_starts_with($rootTask, 'http://') || str_starts_with($rootTask, 'https://'));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string>
     */
    public static function errors(bool $isEnabled, array $settings, bool $agencyConfigured = true): array
    {
        $errors = [];
        $rootTask = trim((string) ($settings['root_task'] ?? ''));

        if ($isEnabled && ! $agencyConfigured) {
            $errors['is_enabled'] = 'Сначала укажите URL портала и вебхук в настройках агентства';
        }

        if ($rootTask !== '' && ! self::isValidRootTaskUrl($rootTask)) {
            $errors['root_task'] = self::ROOT_TASK_URL_ERROR;
        } elseif ($isEnabled && $rootTask === '') {
            $errors['root_task'] = self::ROOT_TASK_REQUIRED_ERROR;
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{root_task: string, search_query: string, parse_comment_works: bool, sync_enabled_at?: string}
     */
    public static function normalize(array $settings, bool $isEnabled = false): array
    {
        $normalized = [
            'root_task' => trim((string) ($settings['root_task'] ?? '')),
            'search_query' => (string) ($settings['search_query'] ?? ''),
            'parse_comment_works' => (bool) ($settings['parse_comment_works'] ?? false),
        ];

        if (! $isEnabled) {
            return $normalized;
        }

        $syncEnabledAt = trim((string) ($settings['sync_enabled_at'] ?? ''));
        $normalized['sync_enabled_at'] = $syncEnabledAt !== ''
            ? $syncEnabledAt
            : now()->format('Y-m-d');

        return $normalized;
    }
}
