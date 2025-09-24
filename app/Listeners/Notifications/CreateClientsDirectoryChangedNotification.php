<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\ClientsDirectoryChanged;
use App\Services\NotificationService;

class CreateClientsDirectoryChangedNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(ClientsDirectoryChanged $e): void
    {
        $text = "Были внесены изменения в клиенте [[client]]";

        // Пока отдельного роута карточки клиента нет — ведём на список клиентов/проектов;
        // если есть привязанный проект — из payload он отобразится снизу (как и сейчас).
        $links = [[
            'key'    => 'client',
            'label'  => $e->clientName,
            'route'  => 'system-settings.clients-and-projects',
            'params' => [],
        ]];

        $payload = [
            'product'        => 'clients_directory',
            'category'       => 'other',
            'client_id'      => $e->clientId,
            'client_name'    => $e->clientName,
            'changed_fields' => array_values($e->changedFields),
        ];

        // если передали проект — добавим для нижней ссылки в карточке
        if ($e->projectId && $e->projectName) {
            $payload['project']    = $e->projectName;
        }

        $this->svc->create(
            userId:    $e->userId,
            text:      $text,
            links:     $links,
            type:      'clients.directory.changed',
            payload:   $payload,
            projectId: $e->projectId,
        );
    }
}
