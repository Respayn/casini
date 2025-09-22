<?php

namespace App\Repositories\Notifications;

use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationRepository
{
    public function getByUserId(int $userId): Collection
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(500)
            ->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return (int) Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function markAllAsRead(int $userId): void
    {
        Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function create(
        int $userId,
        string $text,
        ?string $linkUrl = null,
        ?string $type = null,
        array $payload = [],
        ?int $projectId = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'text' => $text,
            'link_url' => $linkUrl,
            'type' => $type,
            'payload' => $payload,
            'project_id' => $projectId,
        ]);
    }
}
