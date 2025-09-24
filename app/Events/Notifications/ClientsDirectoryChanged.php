<?php

namespace App\Events\Notifications;

class ClientsDirectoryChanged
{
    public function __construct(
        public int $userId,
        public int $clientId,
        public string $clientName,
        /** может быть null, если правили без привязки к конкретному проекту */
        public ?int $projectId = null,
        public ?string $projectName = null,
        /** @var string[] */
        public array $changedFields = [],
    ) {}
}
