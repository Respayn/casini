<?php

namespace App\Repositories\Notifications;

use App\Models\Notification;
use Illuminate\Support\Collection;

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
        array $links = [],
        ?string $type = null,
        array $payload = [],
        ?int $projectId = null,
        ?string $notifiableType = null,
        ?int $notifiableId = null,
    ): Notification {
        return Notification::create([
            'user_id'         => $userId,
            'text'            => $text,
            'link_url'        => $linkUrl,
            'links'           => $links,
            'type'            => $type,
            'payload'         => $payload,
            'project_id'      => $projectId,
            'notifiable_type' => $notifiableType ?? \App\Models\User::class,
            'notifiable_id'   => $notifiableId ?? $userId,
        ]);
    }
}
