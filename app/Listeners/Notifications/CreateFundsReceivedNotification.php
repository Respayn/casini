<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\FundsReceived;
use App\Services\NotificationService;

class CreateFundsReceivedNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(FundsReceived $e): void
    {
        $text = "Поступление рекламных средств на сумму {$e->amount} ₽ от [[client]]";

        $links = [[
            'key'    => 'client',
            'label'  => "Клиент #{$e->clientId}",
            'route'  => 'system-settings.clients-and-projects.projects.manage',
            'params' => ['projectId' => $e->projectId],
        ]];

        $payload = [
            'product'   => 'funds',
            'project'   => $e->projectName,
            'client_id' => $e->clientId,
            'client'    => $e->clientName,
            'amount'    => $e->amount,
            'doc_no'    => $e->docNo,
            'posted_at' => optional($e->postedAt)->toIso8601String(),
        ];

        $this->svc->create(
            userId:    $e->userId,
            text:      $text,
            linkUrl:   null,
            links:     $links,
            type:      'funds.received',
            payload:   $payload,
            projectId: $e->projectId,
        );
    }
}
