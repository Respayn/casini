<?php

namespace App\Events\Notifications;

class IntegrationSyncFailed
{
    public const TYPE = 'integrations.sync.failed';

    public function __construct(
        public int $projectId,
        public string $error,
        public string $collector,
    ) {}
}
