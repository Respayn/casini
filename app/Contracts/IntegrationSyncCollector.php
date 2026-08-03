<?php

namespace App\Contracts;

use App\Data\IntegrationSync\IntegrationSyncCollectContext;
use App\Data\IntegrationSync\IntegrationSyncResult;

interface IntegrationSyncCollector
{
    /**
     * Стабильный ключ коллектора (пишется в integration_sync_items.collector).
     */
    public function key(): string;

    public function collect(IntegrationSyncCollectContext $context): IntegrationSyncResult;
}
