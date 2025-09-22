<?php

namespace App\Services;

use App\Repositories\Notifications\NotificationRepository;
use App\Models\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(private NotificationRepository $repo) {}

    public function getUserNotifications(int $userId): Collection
    {
        return $this->repo->getByUserId($userId);
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->repo->getUnreadCount($userId);
    }

    public function markAllAsRead(int $userId): void
    {
        $this->repo->markAllAsRead($userId);
    }

    public function create(
        int $userId, string $text, ?string $linkUrl = null,
        ?string $type = null, array $payload = [], ?int $projectId = null
    ): Notification {
        return $this->repo->create($userId, $text, $linkUrl, $type, $payload, $projectId);
    }
}
