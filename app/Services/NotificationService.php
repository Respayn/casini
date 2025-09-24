<?php

namespace App\Services;

use App\Data\Notifications\NotificationData;
use App\Data\Notifications\ProjectNotificationData;
use App\Repositories\Notifications\NotificationRepository;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

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

    public function getUserNotificationDTOs(int $userId): DataCollection
    {
        $items = $this->repo->getByUserId($userId);

        $dtos = $items->map(function ($n) {
            return $n->project_id
                ? ProjectNotificationData::fromModel($n)
                : NotificationData::fromModel($n);
        });

        return new DataCollection(NotificationData::class, $dtos->all());
    }
}
