<?php

namespace App\Services\IntegrationSync;

use App\Repositories\IntegrationRepository;
use App\Support\SafeLogger;

/**
 * Итог ночного / ручного съёма на связке клиенто-проект + интеграция.
 * Показывается в окне настроек красным блоком (integrationError).
 */
class IntegrationSyncIssueStatus
{
    public const KIND_KEY = 'sync_issue_kind';

    public const MESSAGE_KEY = 'sync_issue_message';

    public const KIND_ACCESS = 'access';

    public const KIND_OTHER = 'other';

    public function __construct(
        private readonly IntegrationRepository $integrations,
    ) {}

    /**
     * @return list<string>
     */
    public static function preservedKeys(): array
    {
        return [self::KIND_KEY, self::MESSAGE_KEY];
    }

    /**
     * Служебные ключи съёма не приходят из формы: переносим из предыдущих settings.
     *
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function mergePreserved(array $previous, array $incoming): array
    {
        foreach (self::preservedKeys() as $key) {
            if (! array_key_exists($key, $incoming) && array_key_exists($key, $previous)) {
                $incoming[$key] = $previous[$key];
            }
        }

        return $incoming;
    }

    public static function messageFromSettings(?array $settings): ?string
    {
        if ($settings === null) {
            return null;
        }

        $message = trim((string) ($settings[self::MESSAGE_KEY] ?? ''));

        return $message !== '' ? $message : null;
    }

    public function recordSuccess(int $projectId, string $collector): void
    {
        $code = IntegrationManualRefreshProgress::accessIntegrationCode($collector);

        $this->integrations->mutateProjectIntegrationSettings(
            $projectId,
            $code,
            static function (array $settings): array {
                unset($settings[self::KIND_KEY], $settings[self::MESSAGE_KEY]);

                return $settings;
            },
        );
    }

    public function recordFailure(int $projectId, string $collector, string $rawError): void
    {
        $isAccess = SafeLogger::isIntegrationAccessError($rawError);
        $kind = $isAccess ? self::KIND_ACCESS : self::KIND_OTHER;
        $name = IntegrationManualRefreshProgress::accessIntegrationName($collector);
        $code = IntegrationManualRefreshProgress::accessIntegrationCode($collector);
        $message = $isAccess
            ? ($code === 'google_sheets'
                ? 'Доступ к Google Таблицы больше не действует. Откройте таблицу на почту служебной учётки.'
                : 'Доступ к '.$name.' больше не действует. Подключите его заново.')
            : 'Не удалось обновить данные. Следующая попытка будет ночью.';

        $this->integrations->mutateProjectIntegrationSettings(
            $projectId,
            $code,
            static function (array $settings) use ($kind, $message): array {
                $currentKind = $settings[self::KIND_KEY] ?? null;

                if ($currentKind === self::KIND_ACCESS && $kind !== self::KIND_ACCESS) {
                    return $settings;
                }

                $settings[self::KIND_KEY] = $kind;
                $settings[self::MESSAGE_KEY] = $message;

                return $settings;
            },
        );
    }
}
