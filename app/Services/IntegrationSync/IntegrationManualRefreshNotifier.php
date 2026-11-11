<?php

namespace App\Services\IntegrationSync;

use App\Repositories\AgencyRepository;
use App\Services\NotificationService;

class IntegrationManualRefreshNotifier
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly AgencyRepository $agencies,
    ) {}

    /**
     * @param  list<int>  $userIds
     */
    public function notifyUsers(
        array $userIds,
        int $agencyId,
        string $startedProduct,
        string $runId,
        int $updated,
        int $failed,
        int $skipped,
    ): void {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $userIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            return;
        }

        $text = 'Данные в [[channels]] и [[statistics]] обновлены';
        if ($failed > 0) {
            $text .= ". Ошибок: {$failed}";
        }

        $links = [
            [
                'key' => 'channels',
                'label' => 'Каналах',
                'route' => 'channels',
                'params' => [],
            ],
            [
                'key' => 'statistics',
                'label' => 'Статистике',
                'route' => 'statistics',
                'params' => [],
            ],
        ];

        $payload = [
            'agency_id' => $agencyId,
            'product' => 'both',
            'started_product' => $startedProduct,
            'updated' => $updated,
            'failed' => $failed,
            'skipped' => $skipped,
            'run_id' => $runId,
        ];

        foreach ($ids as $userId) {
            $this->notifications->create(
                userId: $userId,
                text: $text,
                linkUrl: null,
                links: $links,
                type: IntegrationManualRefreshProgress::TYPE_REFRESHED,
                payload: $payload,
            );
        }
    }

    public function notifyAgency(
        int $agencyId,
        int $fallbackUserId,
        string $startedProduct,
        string $runId,
        int $updated,
        int $failed,
        int $skipped,
    ): void {
        $userIds = $this->agencies->getUserIds($agencyId);

        if ($userIds === []) {
            $userIds = [$fallbackUserId];
        }

        $this->notifyUsers(
            $userIds,
            $agencyId,
            $startedProduct,
            $runId,
            $updated,
            $failed,
            $skipped,
        );
    }
}
