<?php

namespace App\Data\Notifications;

use App\Models\Notification as NotificationModel;

class ProjectNotificationData extends NotificationData
{
    public static function fromModel(NotificationModel $n): static
    {
        /** @var static $dto */
        $dto = parent::fromModel($n);
        return $dto;
    }

    /** Ссылка на проект по известному роуту (без домена) */
    public function projectHref(): ?string
    {
        return $this->projectId
            ? route('system-settings.clients-and-projects.projects.manage', ['projectId' => $this->projectId], false)
            : null;
    }
}
