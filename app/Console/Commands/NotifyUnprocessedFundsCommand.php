<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

class NotifyUnprocessedFundsCommand extends Command
{
    protected $signature = 'drs:notify-unprocessed';

    protected $description = 'Уведомить менеджеров о поступлениях из 1С, необработанных в ДРС дольше трех рабочих дней';

    public function handle(PaymentService $paymentService): int
    {
        $count = $paymentService->notifyUnprocessed();

        $this->info(sprintf('Notifications sent: %d', $count));

        return self::SUCCESS;
    }
}
