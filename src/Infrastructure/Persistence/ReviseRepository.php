<?php

namespace Src\Infrastructure\Persistence;

use Src\Domain\Revises\ReviseRepositoryInterface;
use Carbon\Carbon;

//модели eloquent, которые пригодятся для формирования DTO revise
use App\Models\Payment;
use App\Models\PaymentOperation;

class ReviseRepository implements ReviseRepositoryInterface
{
    /**
     * Получить сверку по бюджетам 
     * @param Carbon $periodFrom
     * @param Carbon $periodTo
     * @param mixed $clientId
     * @param mixed $managerId
     * @param mixed $fetchFromDirect
     * @param mixed $channelId
     * @return void В разработке
     */
    public function getReviseData(Carbon $periodFrom, Carbon $periodTo, $clientId = null, $managerId = null, $fetchFromDirect = false, $channelId = null)
    {
        $formattedPeriodFrom = $periodFrom->format('Y-m-d');
        $formattedPeriodTo = $periodTo->format('Y-m-d');

        //@todo здесь выполняется получение данных
    }
}
