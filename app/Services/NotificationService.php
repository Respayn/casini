<?php

namespace App\Services;

use App\Models\Notification;
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

        $dtos = $items->map(fn($n) => $n->project_id
            ? ProjectNotificationData::fromModel($n)
            : NotificationData::fromModel($n));

        return new DataCollection(NotificationData::class, $dtos->all());
    }

    /** Делегируем репозиторию. Links — массив (route/path/url), домен не прошиваем. */
    public function create(
        int $userId,
        string $text,
        ?string $linkUrl = null,
        array $links = [],
        ?string $type = null,
        array $payload = [],
        ?int $projectId = null,
        ?string $notifiableType = null,
        ?int $notifiableId = null,
    ): Notification {
        return $this->repo->create(
            userId: $userId,
            text: $text,
            linkUrl: $linkUrl,
            links: $links,
            type: $type,
            payload: $payload,
            projectId: $projectId,
            notifiableType: $notifiableType,
            notifiableId: $notifiableId,
        );
    }
}
