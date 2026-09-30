<?php

namespace App\Contracts;

use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;
use Illuminate\Support\Carbon;

interface IntegrationSyncCollector
{
    /**
     * Стабильный ключ коллектора (пишется в integration_sync_items.collector).
     */
    public function key(): string;

    /**
     * Код интеграции из таблицы integrations (yandex_direct, callibri, …).
     */
    public function integrationCode(): string;

    /**
     * Есть ли у проекта включённая интеграция с валидными credentials.
     */
    public function supportsProject(int $projectId): bool;

    public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult;

    /**
     * Съём за период (ночной день, bulk refresh, backfill).
     */
    public function collectRange(int $projectId, Carbon $from, Carbon $to): IntegrationSyncResult;
}
