<?php

namespace App\Listeners\Notifications;

use App\Events\Notifications\FundsIncomeUnprocessed;
use App\Services\NotificationService;

class CreateFundsIncomeUnprocessedNotification
{
    public function __construct(private NotificationService $svc) {}

    public function handle(FundsIncomeUnprocessed $e): void
    {
        $amount = number_format($e->amount, 2, ',', ' ');
        $date = $e->postedAt?->format('d.m.y') ?? '';
        $text = "Необработанное поступление рекламных средств на сумму {$amount} ₽, {$date}, {$e->docNo} от [[client]]";

        $links = [[
            'key' => 'client',
            'label' => $e->clientName,
            'route' => 'drs',
            'params' => [],
        ]];

        $payload = [
            'product' => 'funds',
            'category' => 'important',
            'project' => $e->projectName,
            'client_id' => $e->clientId,
            'client' => $e->clientName,
            'amount' => $e->amount,
            'doc_no' => $e->docNo,
            'posted_at' => optional($e->postedAt)->toIso8601String(),
        ];

        $this->svc->create(
            userId: $e->userId,
            text: $text,
            links: $links,
            type: 'funds.unprocessed',
            payload: $payload,
            projectId: $e->projectId,
        );
    }
}
