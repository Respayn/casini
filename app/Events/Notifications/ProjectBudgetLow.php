<?php

namespace App\Events\Notifications;

class ProjectBudgetLow
{
    public function __construct(
        public int $userId,              // кому показать
        public int $projectId,
        public string $projectName,
        public float $remaining,         // остаток
        public ?string $instrument = null, // "Яндекс Директ" и т.п.
        public ?int $channelId = null    // если есть внутренний id канала
    ) {}
}
